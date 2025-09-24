<?php

namespace App\DataFixtures;

use App\Entity\Game;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $game = new Game();
            $game->setTitle("Jeu VR $i");
            $game->setDescription("Ceci est la description du jeu VR numéro $i. Un jeu immersif en réalité virtuelle !");
            $game->setReleaseAt(new \DateTimeImmutable(sprintf('202%d-0%d-01', rand(0, 3), rand(1, 9))));
            $game->setStudio("Studio $i");
            // $game->setPicture("https://picsum.photos/seed/game$i/600/400");

            $manager->persist($game);
        }

        $manager->flush();
    }
}
