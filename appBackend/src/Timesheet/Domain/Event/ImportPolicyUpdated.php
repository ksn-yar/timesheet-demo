<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Event;

use App\Timesheet\Domain\ValueObject\ImportPolicyId;
use DateTimeImmutable;

/** Событие обновления политики импорта. */
final readonly class ImportPolicyUpdated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ImportPolicyId $importPolicyId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
