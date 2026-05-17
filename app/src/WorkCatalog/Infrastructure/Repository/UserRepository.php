<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Repository;

use App\Persistence\Repository\UserRepository as UserOrmRepository;
use App\WorkCatalog\Domain\ValueObject\RoleId;

/** Реализация проверок пользователей для WorkCatalog через Persistence-репозиторий. */
final class UserRepository
{
    public function __construct(
        private readonly UserOrmRepository $ormRepository,
    ) {}

    public function existsByRoleId(RoleId $roleId): bool
    {
        return $this->ormRepository->countActiveByRoleId($roleId->value()) > 0;
    }
}
