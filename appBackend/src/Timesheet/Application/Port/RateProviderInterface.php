<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

/** Предоставляет актуальную ставку для сотрудника и вида работ. */
interface RateProviderInterface
{
    /** Возвращает актуальную ставку для сотрудника и вида работ. Возвращает '0' если ставка не найдена. */
    public function getCurrentRate(string $employeeId, string $workId): string;
}
