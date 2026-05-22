<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

use App\Timesheet\Application\Dto\ListTicketsOutputDto;

/** Контракт представления результата Use Case получения списка тикетов. */
interface ListTicketsOutputPortInterface
{
    public function present(ListTicketsOutputDto $dto): void;
}
