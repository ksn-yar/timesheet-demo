<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use DomainException;

/** Пользователь с указанным email уже существует. */
final class DuplicateEmailException extends DomainException
{
    public function __construct(string $email)
    {
        parent::__construct("Пользователь с email «{$email}» уже существует.");
    }
}
