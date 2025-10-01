<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

use function array_fill_callback;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly Generator              $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue   $countRatingsPerValue
    )
    {
    }

    public function load(ObjectManager $manager): void
    {

        $users = $manager->getRepository(User::class)->findAll();
        $tags = $manager->getRepository(Tag::class)->findAll();

        $videoGames = array_fill_callback(0, 50, fn(int $index): VideoGame => (new VideoGame)
            ->setTitle(sprintf('Jeu vidéo %d', $index))
            ->setDescription($this->faker->paragraphs(10, true))
            ->setReleaseDate(new DateTimeImmutable())
            ->setTest($this->faker->paragraphs(6, true))
            ->setRating(($index % 5) + 1)
            ->setImageName(sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );

        // on ajoute entre 0 et 3 tags pour chaque jeu
        foreach ($videoGames as $videoGame) {
            for ($i = 0; $i < random_int(0, 3); $i++) {
                $videoGame->addTag($tags[array_rand($tags,1)]);
            }
        }

        array_walk($videoGames, [$manager, 'persist']);

        $manager->flush();

        // on ajoute des reviews pour chaque jeu (entre 1 et 5)
        $videoGames = $manager->getRepository(VideoGame::class)->findAll();
        foreach ($videoGames as $videoGame) {
            $reviews = array_fill_callback(0, rand(0,3), fn(int $index): Review => (new Review)
                ->setRating($this->faker->numberBetween(1, 5))
                ->setUser($this->faker->randomElement($users))
                ->setVideoGame($videoGame)
                ->setComment(rand(0, 2)===1 ? $this->faker->paragraphs(1, true) : null)
            );
            array_walk($reviews, [$manager, 'persist']);
            $manager->flush();
        }
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, TagFixtures::class];
    }
}
