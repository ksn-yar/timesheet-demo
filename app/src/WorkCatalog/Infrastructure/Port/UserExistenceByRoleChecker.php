<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Port;

use App\WorkCatalog\Application\Port\UserExistenceByRoleCheckerInterface;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use Doctrine\DBAL\Connection;

/** Проверяет наличие пользователей, привязанных к роли, через DBAL-запрос. */
final readonly class UserExistenceByRoleChecker implements UserExistenceByRoleCheckerInterface
{
    public function __construct(
        private Connection $connection,
    ) {}

    public function hasUsersByRole(RoleId $roleId): bool
    {
        /** @var int|string $count */
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM users WHERE role_id = :roleId',
            ['roleId' => $roleId->value()],
        );

        return (int) $count > 0;
    }
}
