<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\UpdateGroupInputDto;

/** Порт входящего Use Case обновления группы. */
interface UpdateGroupUseCaseInterface
{
    public function execute(UpdateGroupInputDto $input): void;
}
