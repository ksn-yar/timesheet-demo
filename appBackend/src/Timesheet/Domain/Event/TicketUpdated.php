<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Event;

use App\Timesheet\Domain\ValueObject\TicketId;
use DateTimeImmutable;

/** Событие обновления тикета. */
final readonly class TicketUpdated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public TicketId $ticketId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
