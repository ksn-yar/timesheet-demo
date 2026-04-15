<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Event;

use App\Timesheet\Domain\ValueObject\TicketId;
use DateTimeImmutable;

/** Событие добавления тикета вручную. */
final readonly class TicketsAdded
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public TicketId $ticketId,
        public string $employeeId,
        public string $taskId,
        public string $workId,
        public string $date,
        public string $hours,
        public string $rateSnapshot,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
