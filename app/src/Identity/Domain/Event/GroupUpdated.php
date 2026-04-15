<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Identity\Domain\ValueObject\GroupId;
use DateTimeImmutable;
use DateTimeZone;

/** Событие обновления атрибутов группы. */
final readonly class GroupUpdated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public GroupId $groupId,
    ) {
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
