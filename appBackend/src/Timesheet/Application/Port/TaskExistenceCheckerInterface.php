<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

/** Проверяет существование задачи в контексте управления проектами. */
interface TaskExistenceCheckerInterface
{
    public function taskExists(string $taskId): bool;
}
