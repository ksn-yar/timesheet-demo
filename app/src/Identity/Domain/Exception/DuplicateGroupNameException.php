<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use DomainException;

/** Группа с указанным названием уже существует. */
final class DuplicateGroupNameException extends DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Группа с названием «{$name}» уже существует.");
    }
}
