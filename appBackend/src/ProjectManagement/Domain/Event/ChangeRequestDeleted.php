<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Event;

use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use DateTimeImmutable;

/** Событие удаления запроса на изменение. */
final readonly class ChangeRequestDeleted
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public ChangeRequestId $changeRequestId,
    ) {
        $this->occurredAt = new DateTimeImmutable();
    }
}
