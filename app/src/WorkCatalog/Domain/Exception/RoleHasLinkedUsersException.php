<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Невозможно удалить роль, к которой привязаны пользователи. */
final class RoleHasLinkedUsersException extends DomainException
{
    public function __construct(string $roleId)
    {
        parent::__construct("Невозможно удалить роль «{$roleId}»: есть привязанные пользователи.");
    }
}
