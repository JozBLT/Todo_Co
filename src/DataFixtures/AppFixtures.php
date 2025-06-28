<?php

namespace App\DataFixtures;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher) {}

    public function load(ObjectManager $manager): void
    {
        // Utilisateur "anonyme"
        $anonymous = new User();
        $anonymous->setUsername('anonyme');
        $anonymous->setEmail('anonyme@todoco.fr');
        $anonymous->setRoles(['ROLE_USER']);
        $anonymous->setPassword($this->passwordHasher->hashPassword($anonymous, 'password'));
        $manager->persist($anonymous);

        // Utilisateur classique
        $user = new User();
        $user->setUsername('user');
        $user->setEmail('user@todoco.fr');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
        $manager->persist($user);

        // Administrateur
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@todoco.fr');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        // Tâches de démo
        foreach (range(1, 3) as $i) {
            $task = new Task();
            $task->setTitle("Tâche admin $i");
            $task->setContent("Contenu de la tâche $i (admin)");
            $task->setAuthor($admin);
            $manager->persist($task);
        }

        foreach (range(1, 3) as $i) {
            $task = new Task();
            $task->setTitle("Tâche user $i");
            $task->setContent("Contenu de la tâche $i (user)");
            $task->setAuthor($user);
            $manager->persist($task);
        }

        foreach (range(1, 3) as $i) {
            $task = new Task();
            $task->setTitle("Tâche anonyme $i");
            $task->setContent("Contenu de la tâche $i (anonyme)");
            $task->setAuthor($anonymous);
            $manager->persist($task);
        }

        $manager->flush();
    }
}
