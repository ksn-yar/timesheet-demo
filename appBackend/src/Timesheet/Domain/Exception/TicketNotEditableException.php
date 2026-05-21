<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

use DomainException;

/** Тикет недоступен для редактирования. */
final class TicketNotEditableException extends DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("Тикет «{$id}» недоступен для редактирования.");
    }
}
