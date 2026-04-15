<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Identity\Domain\ValueObject\GroupId;
use App\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use DateTimeZone;

/** Событие назначения пользователя в группу. */
final readonly class UserAssignedToGroup
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public UserId $userId,
        public GroupId $groupId,
    ) {
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
