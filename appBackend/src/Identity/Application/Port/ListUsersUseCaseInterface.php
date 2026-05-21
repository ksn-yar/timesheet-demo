<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\ListUsersInputDto;

/** Порт входящего Use Case получения списка пользователей. */
interface ListUsersUseCaseInterface
{
    public function execute(ListUsersInputDto $input): void;
}
