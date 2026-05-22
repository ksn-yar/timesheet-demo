<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\ListGroupsOutputDto;

/** Контракт представления результата Use Case получения списка групп. */
interface ListGroupsOutputPortInterface
{
    public function present(ListGroupsOutputDto $dto): void;
}
