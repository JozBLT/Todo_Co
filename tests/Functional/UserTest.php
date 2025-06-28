<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserTest extends WebTestCase
{
    public function testAdminUserCanAccessUserList(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdminUser());

        $client->request('GET', '/users');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('table');
    }

    public function testBasicUserCannotAccessUserList(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getRegularUser());

        $client->request('GET', '/users');

        $this->assertResponseStatusCodeSame(403); // accès interdit
    }

    public function testAnonymousCannotAccessUserRoutes(): void
    {
        $client = static::createClient();
        $userId = $this->getRegularUser()->getId();

        $protectedRoutes = [
            '/users',
            '/users/create',
            '/users/' . $userId . '/edit',
        ];

        foreach ($protectedRoutes as $url) {
            $client->request('GET', $url);

            // Vérifie qu'on est redirigé (vers /login)
            $this->assertResponseRedirects('/login');

            // Suivre la redirection et vérifier la page de login
            $client->followRedirect();
            $this->assertSelectorExists('form[action="/login"]');
        }
    }

    public function testAdminCanSubmitUserCreationForm(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->loginUser($this->getAdminUser());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            // Accès au formulaire
            $crawler = $client->request('GET', '/users/create');
            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('form');

            // Soumission du formulaire avec des données valides
            $form = $crawler->selectButton("Ajouter")->form([
                'user[username]' => 'testuser',
                'user[email]' => 'testuser@todoco.fr',
                'user[password][first]' => 'securepass',
                'user[password][second]' => 'securepass',
            ]);

            $client->submit($form);
            $this->assertResponseRedirects('/users');

            $client->followRedirect();
            $this->assertSelectorTextContains('.alert-success', "L'utilisateur a bien été ajouté.");
        } finally {
            $connection->rollBack();
        }
    }

    public function testAdminCanUpdateUserRole(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->loginUser($this->getAdminUser());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            // Récupération d’un utilisateur classique
            $user = $this->getRegularUser();
            $this->assertNotContains('ROLE_ADMIN', $user->getRoles());

            // Accès au formulaire d’édition
            $crawler = $client->request('GET', '/users/' . $user->getId() . '/edit');
            $this->assertResponseIsSuccessful();

            // Soumission du formulaire avec rôle admin activé
            $form = $crawler->selectButton('Modifier')->form([
                'user[username]' => $user->getUsername(),
                'user[email]' => $user->getEmail(),
                'user[roles][0]' => 'ROLE_ADMIN', // case à cocher
            ]);

            $client->submit($form);
            $this->assertResponseRedirects('/users');

            $client->followRedirect();
            $this->assertSelectorTextContains('.alert-success', "L'utilisateur a bien été modifié.");

            // Rechargement de l'entité pour vérifier le rôle
            $updatedUser = $em->getRepository(User::class)->find($user->getId());
            $this->assertContains('ROLE_ADMIN', $updatedUser->getRoles());

        } finally {
            $connection->rollBack();
        }
    }

    private function getAdminUser(): User
    {
        return static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(User::class)
            ->findOneByEmail('admin@todoco.fr');
    }

    private function getRegularUser(): User
    {
        return static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(User::class)
            ->findOneByEmail('user@todoco.fr');
    }
}
