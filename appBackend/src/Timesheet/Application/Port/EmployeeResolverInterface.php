<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

/** Разрешает внешний идентификатор сотрудника во внутренний UUID. */
interface EmployeeResolverInterface
{
    /** Разрешает внешний идентификатор сотрудника во внутренний UUID. $matchBy: 'email' или 'externalId'. */
    public function resolve(string $value, string $matchBy): ?string;
}
