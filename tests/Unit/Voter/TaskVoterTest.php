<?php

namespace App\Tests\Unit\Voter;

use App\Entity\Task;
use App\Entity\User;
use App\Security\Voter\TaskVoter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->voter = new TaskVoter();
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testAnonymousUserCannotDelete(): void
    {
        $task = new Task();
        $this->token->method('getUser')->willReturn(null);

        $result = $this->voter->vote($this->token, $task, [TaskVoter::DELETE]);
        $this->assertSame(-1, $result); // ACCESS_DENIED
    }

    public function testAdminCanDeleteAnyTask(): void
    {
        $admin = new User();
        $admin->setEmail('admin@todoco.fr');
        $admin->setRoles(['ROLE_ADMIN']);

        $task = new Task();

        $this->token->method('getUser')->willReturn($admin);

        $result = $this->voter->vote($this->token, $task, [TaskVoter::DELETE]);
        $this->assertSame(1, $result); // ACCESS_GRANTED
    }

    public function testAuthorCanDeleteOwnTask(): void
    {
        $user = new User();
        $user->setEmail('user@todoco.fr');

        $task = new Task();
        $task->setAuthor($user);

        $this->token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($this->token, $task, [TaskVoter::DELETE]);
        $this->assertSame(1, $result); // ACCESS_GRANTED
    }

    /**
     * @throws ReflectionException
     */
    public function testUserCannotDeleteOthersTask(): void
    {
        $user = new User();
        $otherUser = new User();

        $this->setEntityId($user, 1);
        $this->setEntityId($otherUser, 2);

        $task = new Task();
        $task->setAuthor($otherUser);

        $this->token->method('getUser')->willReturn($user);

        $result = $this->voter->vote($this->token, $task, [TaskVoter::DELETE]);
        $this->assertSame(-1, $result); // ACCESS_DENIED
    }

    /**
     * @throws ReflectionException
     */
    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }
}
