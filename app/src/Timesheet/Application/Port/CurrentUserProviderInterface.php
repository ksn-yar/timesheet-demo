<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

/** Предоставляет информацию о текущем аутентифицированном пользователе. */
interface CurrentUserProviderInterface
{
    public function getCurrentUserId(): string;

    public function getCurrentUserRole(): string;

    public function isAdmin(): bool;

    public function isManager(): bool;

    public function isEmployee(): bool;
}
