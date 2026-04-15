<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\Persistence\Entity\Task as TaskOrmEntity;
use App\Persistence\Repository\TaskRepository as TaskOrmRepository;
use App\ProjectManagement\Domain\Entity\Task;
use App\ProjectManagement\Domain\Repository\TaskRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\TaskId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища Task.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class TaskRepository implements TaskRepositoryInterface
{
    public function __construct(
        private readonly TaskOrmRepository $ormRepository,
    ) {}

    public function save(Task $task): void
    {
        $existingOrmEntity = $this->ormRepository->find($task->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $task);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($task);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(TaskId $id): ?Task
    {
        $ormEntity = $this->ormRepository->find($id->value());

        if (null === $ormEntity) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    /** @return Task[] */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $ormEntities = $this->ormRepository->findAllPaginated($criteria, $page, $perPage);

        return array_map(
            fn (TaskOrmEntity $ormEntity): Task => $this->toDomainEntity($ormEntity),
            $ormEntities,
        );
    }

    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countAll($criteria);
    }

    public function hasTicketsForTask(TaskId $taskId): bool
    {
        return $this->ormRepository->hasTicketsForTask($taskId->value());
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(Task $task): TaskOrmEntity
    {
        $ormEntity = new TaskOrmEntity();
        $ormEntity->setId($task->getId()->value());
        $ormEntity->setProjectId($task->getProjectId()?->value());
        $ormEntity->setCrId($task->getCrId()?->value());
        $ormEntity->setName($task->getName());
        $ormEntity->setDescription($task->getDescription());
        $ormEntity->setEstimate($task->getEstimate()?->value());
        $ormEntity->setDeletedAt($task->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(TaskOrmEntity $ormEntity, Task $task): void
    {
        $ormEntity->setName($task->getName());
        $ormEntity->setDescription($task->getDescription());
        $ormEntity->setEstimate($task->getEstimate()?->value());
        $ormEntity->setDeletedAt($task->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(TaskOrmEntity $ormEntity): Task
    {
        return Task::restore(
            $ormEntity->getId(),
            $ormEntity->getProjectId(),
            $ormEntity->getCrId(),
            $ormEntity->getName(),
            $ormEntity->getDescription(),
            $ormEntity->getEstimate(),
            $ormEntity->getDeletedAt(),
        );
    }
}
