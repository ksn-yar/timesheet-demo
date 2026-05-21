<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Port;

use App\Persistence\Repository\TaskRepository;
use App\Timesheet\Application\Port\TaskExistenceCheckerInterface;

/** Проверяет существование задачи через Doctrine ORM. */
final readonly class DoctrineTaskExistenceChecker implements TaskExistenceCheckerInterface
{
    public function __construct(
        private TaskRepository $taskRepository,
    ) {}

    public function taskExists(string $taskId): bool
    {
        $task = $this->taskRepository->find($taskId);

        return null !== $task && null === $task->getDeletedAt();
    }
}
