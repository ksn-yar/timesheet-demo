<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Event;

use App\Reporting\Domain\ValueObject\ReportId;
use DateTimeImmutable;

/** Событие создания нового отчёта. */
final readonly class ReportAdded
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ReportId $reportId,
        public string $createdBy,
        public DateTimeImmutable $periodFrom,
        public DateTimeImmutable $periodTo,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
