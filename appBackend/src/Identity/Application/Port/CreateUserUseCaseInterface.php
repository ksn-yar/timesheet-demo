<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\CreateUserInputDto;

/** Порт входящего Use Case создания пользователя. */
interface CreateUserUseCaseInterface
{
    public function execute(CreateUserInputDto $input): void;
}
