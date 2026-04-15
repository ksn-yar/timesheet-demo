<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

use DomainException;

/** Нарушение владения: попытка доступа к тикету другого сотрудника. */
final class TicketOwnershipViolationException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Доступ запрещён: тикет принадлежит другому сотруднику.');
    }
}
