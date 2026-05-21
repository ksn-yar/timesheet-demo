<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Port;

use App\ProjectManagement\Application\Dto\ListTasksOutputDto;

/** Контракт представления результата Use Case получения списка задач. */
interface ListTasksOutputPortInterface
{
    public function present(ListTasksOutputDto $dto): void;
}
