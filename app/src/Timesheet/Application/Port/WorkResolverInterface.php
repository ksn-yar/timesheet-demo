<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

/** Разрешает внешний идентификатор вида работ во внутренний UUID. */
interface WorkResolverInterface
{
    /** Разрешает внешний идентификатор вида работ во внутренний UUID. $matchBy: 'name' или 'externalId'. */
    public function resolve(string $value, string $matchBy): ?string;
}
