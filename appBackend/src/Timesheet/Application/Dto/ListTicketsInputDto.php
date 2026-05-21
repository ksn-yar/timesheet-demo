<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

use DateTimeImmutable;

/** Входные данные Use Case получения списка тикетов с фильтрацией и пагинацией. */
final readonly class ListTicketsInputDto
{
    public function __construct(
        public ?string $employeeId = null,
        public ?string $projectId = null,
        public ?string $crId = null,
        public ?string $taskId = null,
        public ?string $workId = null,
        public ?DateTimeImmutable $dateFrom = null,
        public ?DateTimeImmutable $dateTo = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
