<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\ChangeUserGroupInputDto;

/** Порт входящего Use Case изменения группы пользователя. */
interface ChangeUserGroupUseCaseInterface
{
    public function execute(ChangeUserGroupInputDto $input): void;
}
