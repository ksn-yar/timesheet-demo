<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use DateTimeImmutable;

/** Событие обновления запроса на изменение. */
final readonly class ChangeRequestUpdated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ChangeRequestId $changeRequestId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
