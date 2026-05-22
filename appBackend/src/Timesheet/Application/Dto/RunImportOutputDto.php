<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Выходные данные Use Case запуска импорта тикетов. */
final readonly class RunImportOutputDto
{
    /** @param array<int, array<string, mixed>> $logEntries */
    public function __construct(
        public int $imported,
        public int $duplicates,
        public int $errors,
        public array $logEntries,
    ) {}
}
