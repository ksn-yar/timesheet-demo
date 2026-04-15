<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use DomainException;

/** Невозможно удалить группу, в которой есть активные пользователи. */
final class GroupHasActiveUsersException extends DomainException
{
    public function __construct(string $groupId)
    {
        parent::__construct("Невозможно удалить группу «{$groupId}»: есть активные пользователи.");
    }
}
