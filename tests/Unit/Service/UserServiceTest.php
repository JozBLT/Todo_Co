<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testGetAllUsers(): void
    {
        $mockedUsers = [
            (new User())->setEmail('user1@todoco.fr'),
            (new User())->setEmail('user2@todoco.fr'),
        ];

        $repository = $this->createMock(UserRepository::class);
        $repository->expects($this->once())
            ->method('findAll')
            ->willReturn($mockedUsers);

        $service = new UserService($repository);

        $result = $service->getAllUsers();

        $this->assertSame($mockedUsers, $result);
    }
}
