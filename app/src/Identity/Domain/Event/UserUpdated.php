<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use DateTimeZone;

/** Событие обновления атрибутов пользователя. */
final readonly class UserUpdated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public UserId $userId,
    ) {
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
