<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\DeleteUserInputDto;

/** Порт входящего Use Case удаления пользователя. */
interface DeleteUserUseCaseInterface
{
    public function execute(DeleteUserInputDto $input): void;
}
