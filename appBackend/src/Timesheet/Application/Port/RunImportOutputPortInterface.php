<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

use App\Timesheet\Application\Dto\RunImportOutputDto;

/** Контракт представления результата Use Case запуска импорта тикетов. */
interface RunImportOutputPortInterface
{
    public function present(RunImportOutputDto $dto): void;
}
