<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Identity\Domain\ValueObject\GroupId;
use DateTimeImmutable;
use DateTimeZone;

/** Событие мягкого удаления группы. */
final readonly class GroupDeleted
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public GroupId $groupId,
    ) {
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
