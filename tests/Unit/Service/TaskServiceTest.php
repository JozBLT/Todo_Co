<?php

namespace App\Tests\Unit\Service;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use PHPUnit\Framework\TestCase;

class TaskServiceTest extends TestCase
{
    public function testGetTasksByStatus(): void
    {
        $mockedTasks = [
            (new Task())->setTitle('Tâche A'),
            (new Task())->setTitle('Tâche B'),
        ];

        $repository = $this->createMock(TaskRepository::class);
        $repository->expects($this->once())
            ->method('findBy')
            ->with(['isDone' => true], ['createdAt' => 'DESC'])
            ->willReturn($mockedTasks);

        $service = new TaskService($repository);

        $result = $service->getTasksByStatus(true);

        $this->assertSame($mockedTasks, $result);
    }
}
