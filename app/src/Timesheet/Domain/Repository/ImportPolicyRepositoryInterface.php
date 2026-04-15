<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Repository;

use App\Timesheet\Domain\Entity\ImportPolicy;
use App\Timesheet\Domain\ValueObject\ImportPolicyId;

/** Контракт хранилища агрегатов ImportPolicy. Определяет доменные операции доступа к данным. */
interface ImportPolicyRepositoryInterface
{
    public function save(ImportPolicy $policy): void;

    public function findById(ImportPolicyId $id): ?ImportPolicy;

    /** @return ImportPolicy[] */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array;

    public function countAll(array $criteria = []): int;

    public function findActiveBySourceSystem(string $sourceSystem): ?ImportPolicy;
}
