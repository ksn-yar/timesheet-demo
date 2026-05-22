<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Repository;

use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\ValueObject\ProjectId;

/** Контракт хранилища агрегатов Project. Определяет доменные операции доступа к данным. */
interface ProjectRepositoryInterface
{
    public function save(Project $project): void;

    public function findById(ProjectId $id): ?Project;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Project[]
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array;

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int;

    public function countActiveTasksByProjectId(ProjectId $projectId): int;

    public function countActiveChangeRequestsByProjectId(ProjectId $projectId): int;
}
