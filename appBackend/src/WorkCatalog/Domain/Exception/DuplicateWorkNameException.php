<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Вид работ с таким наименованием уже существует. */
final class DuplicateWorkNameException extends DomainException
{
    public function __construct(string $name)
    {
        parent::__construct("Вид работ с наименованием «{$name}» уже существует.");
    }
}
