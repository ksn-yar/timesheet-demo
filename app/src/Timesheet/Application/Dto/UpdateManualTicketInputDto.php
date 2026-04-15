<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

use DateTimeImmutable;

/** Входные данные Use Case обновления тикета. Sentinel '__NOT_SET__' для comment означает отсутствие изменения. */
final readonly class UpdateManualTicketInputDto
{
    public function __construct(
        public string $ticketId,
        public ?DateTimeImmutable $date = null,
        public ?string $hours = null,
        public ?string $workId = null,
        public mixed $comment = '__NOT_SET__',
    ) {}
}
