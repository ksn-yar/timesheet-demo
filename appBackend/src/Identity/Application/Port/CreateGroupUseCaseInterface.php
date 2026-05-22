<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\CreateGroupInputDto;

/** Порт входящего Use Case создания группы. */
interface CreateGroupUseCaseInterface
{
    public function execute(CreateGroupInputDto $input): void;
}
