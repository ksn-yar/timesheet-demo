<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

/** Разрешает внешний идентификатор задачи во внутренний UUID. */
interface TaskResolverInterface
{
    /** Разрешает внешний идентификатор задачи во внутренний UUID. $matchBy: 'name' или 'externalId'. */
    public function resolve(string $value, string $matchBy): ?string;
}
