<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

use DateTimeImmutable;

/** Входные данные Use Case создания тикета вручную. */
final readonly class CreateManualTicketInputDto
{
    public function __construct(
        public string $employeeId,
        public string $taskId,
        public string $workId,
        public DateTimeImmutable $date,
        public string $hours,
        public ?string $comment = null,
    ) {}
}
