<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\Persistence\Entity\Project as ProjectOrmEntity;
use App\Persistence\Repository\ProjectRepository as ProjectOrmRepository;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Enum\ProjectStatus;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища Project.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class ProjectRepository implements ProjectRepositoryInterface
{
    public function __construct(
        private readonly ProjectOrmRepository $ormRepository,
    ) {}

    public function save(Project $project): void
    {
        $existingOrmEntity = $this->ormRepository->find($project->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $project);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($project);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(ProjectId $id): ?Project
    {
        $ormEntity = $this->ormRepository->find($id->value());

        if (null === $ormEntity) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Project[]
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $ormEntities = $this->ormRepository->findAllPaginated($criteria, $page, $perPage);

        return array_map(
            fn (ProjectOrmEntity $ormEntity): Project => $this->toDomainEntity($ormEntity),
            $ormEntities,
        );
    }

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countAll($criteria);
    }

    public function countActiveTasksByProjectId(ProjectId $projectId): int
    {
        return $this->ormRepository->countActiveTasksByProjectId($projectId->value());
    }

    public function countActiveChangeRequestsByProjectId(ProjectId $projectId): int
    {
        return $this->ormRepository->countActiveChangeRequestsByProjectId($projectId->value());
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(Project $project): ProjectOrmEntity
    {
        $ormEntity = new ProjectOrmEntity();
        $ormEntity->setId($project->getId()->value());
        $ormEntity->setClientId($project->getClientId()->value());
        $ormEntity->setName($project->getName());
        $ormEntity->setStatus($project->getStatus()->value);
        $ormEntity->setDescription($project->getDescription());
        $ormEntity->setDeletedAt($project->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(ProjectOrmEntity $ormEntity, Project $project): void
    {
        $ormEntity->setName($project->getName());
        $ormEntity->setStatus($project->getStatus()->value);
        $ormEntity->setDescription($project->getDescription());
        $ormEntity->setDeletedAt($project->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(ProjectOrmEntity $ormEntity): Project
    {
        return Project::restore(
            $ormEntity->getId(),
            $ormEntity->getClientId(),
            $ormEntity->getName(),
            ProjectStatus::from($ormEntity->getStatus()),
            $ormEntity->getDescription(),
            $ormEntity->getDeletedAt(),
        );
    }
}
