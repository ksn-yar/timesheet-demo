<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Repository;

use App\WorkCatalog\Domain\Entity\Work;
use App\WorkCatalog\Domain\ValueObject\WorkId;

/** Контракт хранилища агрегатов Work. Определяет доменные операции доступа к данным. */
interface WorkRepositoryInterface
{
    public function save(Work $work): void;

    public function findById(WorkId $id): ?Work;

    public function findByName(string $name): ?Work;

    /** @return Work[] */
    public function findAll(array $criteria, int $limit, int $offset): array;

    public function countAll(array $criteria): int;
}
