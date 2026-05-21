<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Exception;

use DomainException;

/** Некорректное количество часов в тикете. */
final class InvalidTicketHoursException extends DomainException
{
    public function __construct(string $hours)
    {
        parent::__construct("Количество часов должно быть больше нуля, передано: «{$hours}».");
    }
}
