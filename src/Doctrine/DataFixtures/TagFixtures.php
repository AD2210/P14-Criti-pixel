<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class TagFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // création des tags pour alimenter les fixtures
        $tags = ['FPS','MMORPG','RPG','Shooter','Sports','Strategy','Tactical','Turn-based'];
        foreach ($tags as $index => $tag) {
            $tag = new Tag();
            $tag->setName($tags[$index]);
            $manager->persist($tag);
        }

        $manager->flush();
    }
}
