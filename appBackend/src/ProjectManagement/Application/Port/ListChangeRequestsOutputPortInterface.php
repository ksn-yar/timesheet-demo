<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Port;

use App\ProjectManagement\Application\Dto\ListChangeRequestsOutputDto;

/** Контракт представления результата Use Case получения списка запросов на изменение. */
interface ListChangeRequestsOutputPortInterface
{
    public function present(ListChangeRequestsOutputDto $dto): void;
}
