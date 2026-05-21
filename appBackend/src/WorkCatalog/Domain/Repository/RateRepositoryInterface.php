<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Repository;

use App\WorkCatalog\Domain\Entity\Rate;
use App\WorkCatalog\Domain\ValueObject\RateId;

/** Контракт хранилища агрегатов Rate. Определяет доменные операции доступа к данным. */
interface RateRepositoryInterface
{
    public function save(Rate $rate): void;

    public function findById(RateId $id): ?Rate;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Rate[]
     */
    public function findAll(array $criteria, int $limit, int $offset): array;

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria): int;
}
