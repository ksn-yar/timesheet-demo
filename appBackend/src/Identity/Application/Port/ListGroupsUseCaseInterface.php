<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\ListGroupsInputDto;

/** Порт входящего Use Case получения списка групп. */
interface ListGroupsUseCaseInterface
{
    public function execute(ListGroupsInputDto $input): void;
}
