<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

/** Проверяет существование вида работ в контексте каталога работ. */
interface WorkExistenceCheckerInterface
{
    public function workExists(string $workId): bool;
}
