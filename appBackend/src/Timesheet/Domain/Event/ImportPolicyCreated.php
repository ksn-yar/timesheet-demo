<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Event;

use App\Timesheet\Domain\ValueObject\ImportPolicyId;
use DateTimeImmutable;

/** Событие создания политики импорта. */
final readonly class ImportPolicyCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ImportPolicyId $importPolicyId,
        public string $name,
        public string $sourceSystem,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
