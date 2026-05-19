<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\DeactivateUserInputDto;

/** Порт входящего Use Case деактивации пользователя. */
interface DeactivateUserUseCaseInterface
{
    public function execute(DeactivateUserInputDto $input): void;
}
