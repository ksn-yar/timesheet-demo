<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Элемент списка тикетов в выходных данных Use Case. */
final readonly class TicketItemDto
{
    public function __construct(
        public string $id,
        public string $employeeId,
        public string $employeeName,
        public string $taskId,
        public string $taskName,
        public string $workId,
        public string $workName,
        public string $date,
        public string $hours,
        public ?string $comment,
        public string $rateSnapshot,
        public string $type,
        public ?string $importSource,
        public ?string $externalId,
        public bool $isEditable,
    ) {}
}
