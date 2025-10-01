<?php

namespace App\Tests\Unit;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RatingCalculationTest extends TestCase
{
    private User|null $user;
    private VideoGame|null $videoGame;

    protected function setUp(): void
    {
        // on instancie un user et un video game, necessaire pour instancié les reviews
        $this->user = new User();
        $this->videoGame = new VideoGame();
    }

    #[DataProvider('provideReviewRatingSet')]
    public function testCalculateAverage(int $nb1,int $nb2,int $nb3, int $nb4, int $nb5, int $expected): void
    {
        $nb = [$nb1, $nb2, $nb3, $nb4, $nb5];
        // on itère sur les 5 valeurs (rating)
        for ($i = 0; $i < 5; $i++) {
            // on sélectionne la variable en fonction de l'itération gràce au tableau
            for ($j = 0; $j < $nb[$i]; $j++) {
                $review = new Review();
                $review->setRating($i + 1);
                $review->setVideoGame($this->videoGame);
                $review->setUser($this->user);
                $this->videoGame->addReview($review);
            }
        }

        // on appelle le calculateAverage
        $ratingHandler = new RatingHandler();
        $ratingHandler->calculateAverage($this->videoGame);

        //on teste la valeur de retour du calculateAverage
        $this->assertEquals($expected, $this->videoGame->getAverageRating(), sprintf('Average rating should be 3, got %d', $this->videoGame->getAverageRating())); // on attends 3
    }

    /**
     * Test du countPerValue
     *
     * @return void
     */
    #[DataProvider('provideReviewRatingSet')]
    public function testCountPerValue(int $nb1, int $nb2, int $nb3, int $nb4, int $nb5): void
    {
        $nb = [$nb1, $nb2, $nb3, $nb4, $nb5];
        // on itère sur les 5 valeurs (rating)
        for ($i = 0; $i < 5; $i++) {
            // on sélectionne la variable en fonction de l'itération gràce au tableau
            for ($j = 0; $j < $nb[$i]; $j++) {
                $review = new Review();
                $review->setRating($i + 1);
                $review->setVideoGame($this->videoGame);
                $review->setUser($this->user);
                $this->videoGame->addReview($review);
            }
        }

        // on appelle le countPerValue
        $ratingHandler = new RatingHandler();
        $ratingHandler->countRatingsPerValue($this->videoGame);

        // on vérifie que les valeurs sont correctes
        $expected = [1 => $nb1, 2 => $nb2, 3 => $nb3, 4 => $nb4, 5 => $nb5];
        $this->assertEquals($expected[1], $this->videoGame->getNumberOfRatingsPerValue()->getNumberOfOne());
        $this->assertEquals($expected[2], $this->videoGame->getNumberOfRatingsPerValue()->getNumberOfTwo());
        $this->assertEquals($expected[3], $this->videoGame->getNumberOfRatingsPerValue()->getNumberOfThree());
        $this->assertEquals($expected[4], $this->videoGame->getNumberOfRatingsPerValue()->getNumberOfFour());
        $this->assertEquals($expected[5], $this->videoGame->getNumberOfRatingsPerValue()->getNumberOfFive());
    }


    /**
     * @return array<int, list<int|null>>
     */
    // [nb 1star, nb 2stars, nb 3stars, nb 4stars, nb 5stars, expected average]
    // le but est de vérifier que le calcul est correct et l'arrondi également (ceil)
    public static function provideReviewRatingSet(): array
    {
        return [
            [10,10,10,10,10,3], // 10*1+10*2+10*3+10*4+10*5 = 150/50 = 3 (test du clear sur itération 2 pour countPerValue)
            [2, 0, 3, 0, 1, 3], // 2*1+3*3+1*5 = 16/6 = 2.67 (on vérifie arrondi au sup et pas troncage)
            [0, 0, 0, 0, 0, null], // test qui doit retourner null (average)
            [4, 4, 3, 1, 1, 3], // 4*1+4*2+3*3+1*4+1*5 = 28/13 = 2.15 (on vérifie arrondi au sup et pas arrondi simple)
        ];
    }

    /**
     * Unsets the user, video game and review properties.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->user = null;
        $this->videoGame = null;
    }
}
