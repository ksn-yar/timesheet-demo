<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Identity\Domain\ValueObject\GroupId;
use DateTimeImmutable;
use DateTimeZone;

/** Событие создания группы. */
final readonly class GroupCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public GroupId $groupId,
        public string $name,
    ) {
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
