<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Domain\ValueObject\UserId;

/** Контракт проверки наличия тикетов, привязанных к пользователю. */
interface TicketExistenceCheckerInterface
{
    public function hasTicketsForUser(UserId $userId): bool;
}
