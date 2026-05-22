<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

use DomainException;

/** Тикет не найден по указанному идентификатору. */
final class TicketNotFoundException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Тикет с идентификатором «{$id}» не найден.");
    }
}
