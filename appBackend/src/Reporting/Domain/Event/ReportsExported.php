<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Event;

use App\Reporting\Domain\Enum\ExportFormat;
use DateTimeImmutable;

/** Событие успешной выгрузки набора отчётов в файл. */
final readonly class ReportsExported
{
    public DateTimeImmutable $occurredAt;

    /** @param string[] $reportIds */
    public function __construct(
        public array $reportIds,
        public ExportFormat $format,
        public string $fileRef,
        public DateTimeImmutable $generatedAt,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
