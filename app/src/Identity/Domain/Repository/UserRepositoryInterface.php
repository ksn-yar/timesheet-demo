<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Entity\User;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\GroupId;
use App\Identity\Domain\ValueObject\UserId;

/** Контракт хранилища агрегатов User. Определяет доменные операции доступа к данным. */
interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return array{items: User[], total: int}
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array;

    public function existsByEmail(Email $email): bool;

    public function countActiveUsersByGroupId(GroupId $groupId): int;

    public function countActiveUsersByRoleId(string $roleId): int;
}
