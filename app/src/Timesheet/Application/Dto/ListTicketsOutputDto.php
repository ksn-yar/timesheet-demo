<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Выходные данные Use Case получения списка тикетов. */
final readonly class ListTicketsOutputDto
{
    /**
     * @param TicketItemDto[] $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
