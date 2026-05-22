<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Port;

use App\WorkCatalog\Application\Port\UserExistenceByRoleCheckerInterface;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use App\WorkCatalog\Infrastructure\Repository\UserRepository;

/** Проверяет наличие пользователей, привязанных к роли. */
final readonly class UserExistenceByRoleChecker implements UserExistenceByRoleCheckerInterface
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function hasUsersByRole(RoleId $roleId): bool
    {
        return $this->userRepository->existsByRoleId($roleId);
    }
}
