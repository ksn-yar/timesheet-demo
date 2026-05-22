<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use DateTimeZone;

/** Событие мягкого удаления пользователя. */
final readonly class UserDeleted
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public UserId $userId,
    ) {
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
