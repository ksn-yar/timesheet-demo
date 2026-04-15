<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use DomainException;

/** Попытка деактивировать уже деактивированного пользователя. */
final class UserAlreadyDeactivatedException extends DomainException
{
    public function __construct(string $userId)
    {
        parent::__construct("Пользователь «{$userId}» уже деактивирован.");
    }
}
