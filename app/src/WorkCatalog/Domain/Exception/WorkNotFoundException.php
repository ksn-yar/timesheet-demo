<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Вид работ не найден по указанному идентификатору. */
final class WorkNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Вид работ с идентификатором «{$id}» не найден.");
    }
}
