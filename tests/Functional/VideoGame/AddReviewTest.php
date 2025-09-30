<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use http\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class AddReviewTest extends FunctionalTestCase
{

    /**
     * Test si on affiche le formulaire de review lorsque user logué et pas de review
     *
     * @return void
     */
    public function testShouldShowReviewForm(): void
    {
        $this->ensureReviewNotPublishedByUser('user+0@email.com');

        $this->login('user+0@email.com'); // on logue un user email par défaut
        $this->get('/jeu-video-0');
        self::assertSelectorExists('form');
        self::assertSelectorTextContains('form button, form input[type="submit"]', 'Poster');
    }

    /**
     * Test l'absence du formulaire si user non logué
     *
     * @return void
     */
    public function testShouldNotShowReviewForm(): void
    {
        $crawler = $this->get('/jeu-video-0');
        self::assertTrue($crawler->filter('form')->count() == 0);
    }

    public function testShouldRedirectAfterSubmitForm(): void
    {
        $this->ensureReviewNotPublishedByUser('user+0@email.com');

        $this->login('user+0@email.com');
        $crawler =$this->get('/jeu-video-0');
        $form = $crawler->selectButton('Poster')->form();
        $this->client->submit($form , [
            'review[rating]' => 5,
            'review[comment]' => 'Test'
        ]);

        $savedRating = $this->getEntityManager()
            ->getRepository(Review::class)
            ->findOneBy([
                'user' => $this->getEntityManager()
                    ->getRepository(User::class)
                    ->findOneBy(['email' => 'user+0@email.com']),
                'videoGame' => $this->getEntityManager()
                    ->getRepository(VideoGame::class)
                    ->findOneBy(['slug' => 'jeu-video-0'])
            ])
        ;

        //Vérification de la sauvegarde de la review dans DB
        self::assertSame(5, $savedRating->getRating()); // on contrôle dans la base que la valeur est bien 5
        self::assertSame('Test', $savedRating->getComment()); // on contrôle le commentaire

        // vérification de la redirection après soumission du formulaire
        self::assertResponseRedirects('/jeu-video-0');
        $crawler = $this->client->followRedirect(); // on suit la redirection

        // on vérifie l'absence du formulaire après la redirection (1 seule review / user / jeu)
        self::assertTrue($crawler->filter('form')->count() == 0);
    }

    public function testShouldShowErrorOnSubmitFormWithWrongData(): void
    {
        $this->ensureReviewNotPublishedByUser('user+0@email.com');

        $this->login('user+0@email.com');
        $crawler =$this->get('/jeu-video-0');
        $form = $crawler->selectButton('Poster')->form();

        self::expectExceptionMessage('Input "review[rating]" cannot take "" as a value (possible values: "1", "2", "3", "4", "5").');
        $this->client->submit($form , [
            'review[rating]' => '',
            'review[comment]' => ''
        ]);
        self::assertResponseIsUnprocessable(); // Lève l'exception avant d'arriver ici
    }

    // Contrôle si une review n'a pas été généré par les fixtures
    private function ensureReviewNotPublishedByUser(string $email = 'user+0@email.com'): void
    {
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => $email]);
        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-0']);

        if (!$user || !$videoGame) {
            return;
        }
        $review = $this->getEntityManager()->getRepository(Review::class)->findOneBy(['videoGame' => $videoGame,'user' => $user]);

        if ($review) { // on supprime la review si elle existe
            $this->getEntityManager()->remove($review);
            $this->getEntityManager()->flush();
        }
    }
}
