<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Event;

use App\Timesheet\Domain\ValueObject\ImportPolicyId;
use DateTimeImmutable;

/** Событие завершения импорта тикетов из внешней системы. */
final readonly class TicketsImported
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ImportPolicyId $importPolicyId,
        public int $imported,
        public int $duplicates,
        public int $errors,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
