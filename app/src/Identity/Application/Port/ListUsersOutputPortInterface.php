<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\ListUsersOutputDto;

/** Контракт представления результата Use Case получения списка пользователей. */
interface ListUsersOutputPortInterface
{
    public function present(ListUsersOutputDto $dto): void;
}
