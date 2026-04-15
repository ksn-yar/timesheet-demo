<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

use DateTimeImmutable;

/** Входные данные Use Case запуска импорта тикетов из внешней системы. */
final readonly class RunImportInputDto
{
    public function __construct(
        public string $importPolicyId,
        public DateTimeImmutable $dateFrom,
        public DateTimeImmutable $dateTo,
    ) {}
}
