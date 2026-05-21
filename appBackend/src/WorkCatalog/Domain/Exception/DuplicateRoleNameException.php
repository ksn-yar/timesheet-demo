<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Роль с таким наименованием уже существует. */
final class DuplicateRoleNameException extends DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Роль с наименованием «{$name}» уже существует.");
    }
}
