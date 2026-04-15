<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\ValueObject\GroupId;

/** Контракт хранилища агрегатов Group. Определяет доменные операции доступа к данным. */
interface GroupRepositoryInterface
{
    public function save(Group $group): void;

    public function findById(GroupId $id): ?Group;

    /** @return array{items: Group[], total: int} */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array;

    public function existsByName(string $name): bool;
}
