<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Port;

use App\WorkCatalog\Application\Dto\ListRolesOutputDto;

/** Контракт представления результата Use Case получения списка ролей. */
interface ListRolesOutputPortInterface
{
    public function present(ListRolesOutputDto $dto): void;
}
