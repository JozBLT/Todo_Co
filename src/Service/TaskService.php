<?php

namespace App\Service;

use App\Repository\TaskRepository;

readonly class TaskService
{
    public function __construct(private TaskRepository $taskRepository)
    {
    }

    /**
     * Retourne les tâches en fonction de leur statut
     */
    public function getTasksByStatus(bool $isDone): array
    {
        return $this->taskRepository->findBy(['isDone' => $isDone], ['createdAt' => 'DESC']);
    }
}
