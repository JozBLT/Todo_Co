<?php

namespace App\Tests\Functional;

use App\Entity\Task;
use App\Entity\User;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TaskTest extends WebTestCase
{
    public function testAuthenticatedUserCanAccessTaskCreationPage(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser());

        $client->request('GET', '/tasks/create');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    /**
     * @throws Exception
     */
    public function testAuthenticatedUserCanSubmitTaskForm(): void
    {
        $client = static::createClient();
        $client->disableReboot(); // empêche Symfony de rebooter le kernel entre les appels

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            $client->loginUser($this->getUser());

            $crawler = $client->request('GET', '/tasks/create');
            $this->assertResponseIsSuccessful();
            $this->assertSelectorExists('form');

            $form = $crawler->selectButton('Ajouter')->form([
                'task[title]' => 'Tâche de test temporaire',
                'task[content]' => 'Contenu temporaire',
            ]);

            $client->submit($form);

            $this->assertResponseRedirects('/tasks');
            $client->followRedirect();
            $this->assertSelectorTextContains('.alert-success', 'La tâche a bien été ajoutée.');
        } finally {
            $connection->rollBack();
        }
    }

    public function testTaskCanBeEditedWithoutChangingAuthor(): void
    {
        $client = static::createClient();
        $client->disableReboot(); // important pour garder l'EntityManager actif
        $client->loginUser($this->getUser());

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $em->getConnection();
        $connection->beginTransaction();

        try {
            $taskId = $this->getUserTaskId(); // tâche existante
            $taskRepository = $em->getRepository(Task::class);

            $originalTask = $taskRepository->find($taskId);
            $originalAuthor = $originalTask->getAuthor();

            $crawler = $client->request('GET', '/tasks/' . $taskId . '/edit');
            $form = $crawler->selectButton('Modifier')->form([
                'task[title]' => 'Titre modifié via test',
                'task[content]' => 'Contenu modifié via test',
            ]);

            $client->submit($form);
            $this->assertResponseRedirects('/tasks');
            $client->followRedirect();

            $this->assertSelectorTextContains('.alert-success', 'La tâche a bien été modifiée.');

            // Recharger l'entité
            $em->clear();
            $modifiedTask = $taskRepository->find($taskId);

            $this->assertSame('Titre modifié via test', $modifiedTask->getTitle());
            $this->assertSame('Contenu modifié via test', $modifiedTask->getContent());
            $this->assertSame($originalAuthor->getId(), $modifiedTask->getAuthor()->getId());
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
        }
    }

    public function testUserCanToggleTask(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->loginUser($this->getUser());

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $taskId = $this->getOtherUserTaskId();

            // Accès à la page pour récupérer le token CSRF du formulaire
            $crawler = $client->request('GET', '/tasks');
            $form = $crawler->filter('form[action="/tasks/' . $taskId . '/toggle"]')->form();
            $client->submit($form);

            $this->assertResponseRedirects('/tasks');
            $client->followRedirect();
            $this->assertSelectorExists('.alert-success');
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
        }
    }

    public function testUserCanDeleteOwnTask(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $client->loginUser($this->getUser());

        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $taskId = $this->getUserTaskId();

            $crawler = $client->request('GET', '/tasks');
            $form = $crawler->filter('form[action="/tasks/' . $taskId . '/delete"]')->form();
            $client->submit($form);

            $this->assertResponseRedirects('/tasks');
            $client->followRedirect();
            $this->assertSelectorTextContains('.alert-success', 'La tâche a bien été supprimée.');
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
        }
    }

    public function testUserCannotDeleteOthersTask(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getUser());

        $otherTaskId = $this->getOtherUserTaskId();

        // L’utilisateur ne doit même pas voir le bouton de suppression
        $client->request('GET', '/tasks');
        $this->assertSelectorNotExists('form[action="/tasks/' . $otherTaskId . '/delete"]');
    }

    private function getUser(): User
    {
        return static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(User::class)
            ->findOneByEmail('user@todoco.fr');
    }

    private function getUserTaskId(): int
    {
        $user = $this->getUser();
        $task = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Task::class)
            ->findOneBy(['author' => $user]);

        if (!$task) {
            throw new LogicException("Aucune tâche trouvée pour l'utilisateur {$user->getEmail()}.");
        }

        return $task->getId();
    }

    private function getOtherUserTaskId(): int
    {
        $user = $this->getUser();
        $task = static::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Task::class)
            ->createQueryBuilder('t')
            ->where('t.author IS NOT NULL')
            ->andWhere('t.author != :user')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$task) {
            throw new LogicException("Aucune tâche appartenant à un autre utilisateur n’a été trouvée.");
        }

        return $task->getId();
    }
}
