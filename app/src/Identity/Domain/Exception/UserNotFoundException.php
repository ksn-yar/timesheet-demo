<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use DomainException;

/** Пользователь не найден по указанному идентификатору. */
final class UserNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Пользователь с идентификатором «{$id}» не найден.");
    }
}
