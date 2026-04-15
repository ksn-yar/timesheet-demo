<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Входные данные Use Case получения тикета по идентификатору. */
final readonly class GetTicketInputDto
{
    public function __construct(
        public string $ticketId,
    ) {}
}
