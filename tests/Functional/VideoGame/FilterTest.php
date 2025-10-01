<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class FilterTest extends FunctionalTestCase
{
    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->clickLink('2');
        self::assertResponseIsSuccessful();
    }

    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }

    #[DataProvider('provideTags')]
    public function testShouldFilterVideoGamesByTag(array $tags): void
    {
        // Convertit les noms de tags en IDs attendus par le formulaire/filtre
        $em = $this->getEntityManager();

        $this->get('/');

        //On construit la requête de filtrage
        $submitData = ['filter[tags]' => []];
        foreach ($tags as $index =>$id) {
            $submitData['filter[tags]'][$index] = $id;
        }

        $this->client->submitForm('Filtrer', $submitData, 'GET');
        self::assertResponseIsSuccessful();

        // Calcule le nombre attendu de jeux ayant tous les tags sélectionnés
        if ($tags === []) {
            // cas sans filtre, compter les jeux de la page (10 par défaut)
            self::assertSelectorCount(10, 'article.game-card');
            return;
        }

        $qb = $em->createQueryBuilder()
            ->select('vg')
            ->from(VideoGame::class, 'vg')
            ->join('vg.tags', 't')
            ->where('t.id IN (:tags)')
            ->groupBy('vg.id')
            ->having('COUNT(DISTINCT t.id) = :tagCount')
            ->setParameter('tags', $tags)
            ->setParameter('tagCount', count($tags));

        $expectedCount = count($qb->getQuery()->getResult());
        self::assertSelectorCount($expectedCount, 'article.game-card');
    }

    public static function provideTags(): array
    {
        return [
            [[]],
            [[1]],
            [[1, 2]],
        ];
    }
}
