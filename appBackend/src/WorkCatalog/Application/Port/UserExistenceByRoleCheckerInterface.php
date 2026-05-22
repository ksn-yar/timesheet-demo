<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Port;

use App\WorkCatalog\Domain\ValueObject\RoleId;

/** Контракт проверки наличия пользователей, привязанных к роли. */
interface UserExistenceByRoleCheckerInterface
{
    public function hasUsersByRole(RoleId $roleId): bool;
}
