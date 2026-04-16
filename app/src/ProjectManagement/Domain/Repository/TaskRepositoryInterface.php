<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Repository;

use App\ProjectManagement\Domain\Entity\Task;
use App\ProjectManagement\Domain\ValueObject\TaskId;

/** Контракт хранилища агрегатов Task. Определяет доменные операции доступа к данным. */
interface TaskRepositoryInterface
{
    public function save(Task $task): void;

    public function findById(TaskId $id): ?Task;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Task[]
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array;

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int;

    public function hasTicketsForTask(TaskId $taskId): bool;
}
