<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\UpdateUserInputDto;

/** Порт входящего Use Case обновления пользователя. */
interface UpdateUserUseCaseInterface
{
    public function execute(UpdateUserInputDto $input): void;
}
