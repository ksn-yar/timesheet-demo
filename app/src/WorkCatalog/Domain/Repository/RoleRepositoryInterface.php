<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Repository;

use App\WorkCatalog\Domain\Entity\Role;
use App\WorkCatalog\Domain\ValueObject\RoleId;

/** Контракт хранилища агрегатов Role. Определяет доменные операции доступа к данным. */
interface RoleRepositoryInterface
{
    public function save(Role $role): void;

    public function findById(RoleId $id): ?Role;

    public function findByName(string $name): ?Role;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Role[]
     */
    public function findAll(array $criteria, int $limit, int $offset): array;

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria): int;

    public function countActiveRatesByRoleId(RoleId $id): int;
}
