<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->loadGame($manager);
        $this->loadUser($manager);
    }

    private function loadGame(ObjectManager $manager)
    {
        for ($i = 1; $i <= 10; $i++) {
            $game = new Game();
            $game->setTitle("Jeu VR $i");
            $game->setDescription("Ceci est la description du jeu VR numéro $i. Un jeu immersif en réalité virtuelle !");
            $game->setReleaseAt(new \DateTimeImmutable(sprintf('202%d-0%d-01', rand(0, 3), rand(1, 9))));
            $game->setStudio("Studio $i");

            $manager->persist($game);
        }

        $manager->flush();
    }

    private function loadUser(ObjectManager $manager)
    {
        foreach ($this->getUserData() as [$pseudo, $email, $roles, $password]) {
            $user = new User();
            $user->setPseudo($pseudo);
            $user->setEmail($email);
            $user->setRoles($roles);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $password)
            );

            $manager->persist($user);
        }

        $manager->flush();
    }

    private function getUserData(): array
    {
        return [
            // $userData = [$pseudo, $email, $roles, password]
            ["splint", "splint@test.fr", ["ROLE_ADMIN"], "123"],
            ["user1", "user1@test.fr", ["ROLE_USER"], "123"]
        ];
    }
}
