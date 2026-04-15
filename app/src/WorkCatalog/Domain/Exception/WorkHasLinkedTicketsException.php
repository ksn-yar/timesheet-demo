<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Exception;

use DomainException;

/** Невозможно удалить вид работ, к которому привязаны тикеты. */
final class WorkHasLinkedTicketsException extends DomainException
{
    public function __construct(string $workId)
    {
        parent::__construct("Невозможно удалить вид работ «{$workId}»: есть привязанные тикеты.");
    }
}
