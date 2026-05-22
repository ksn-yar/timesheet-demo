<?php

declare(strict_types=1);

namespace App\Identity\Domain\Event;

use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\ValueObject\UserId;
use DateTimeImmutable;
use DateTimeZone;

/** Событие создания пользователя. */
final readonly class UserCreated
{
    public DateTimeImmutable $occurredAt;

    public function __construct(
        public UserId $userId,
        public string $name,
        public string $email,
        public SystemRole $systemRole,
    ) {
        $this->occurredAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
