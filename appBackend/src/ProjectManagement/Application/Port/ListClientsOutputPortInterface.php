<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Port;

use App\ProjectManagement\Application\Dto\ListClientsOutputDto;

/** Контракт представления результата Use Case получения списка клиентов. */
interface ListClientsOutputPortInterface
{
    public function present(ListClientsOutputDto $dto): void;
}
