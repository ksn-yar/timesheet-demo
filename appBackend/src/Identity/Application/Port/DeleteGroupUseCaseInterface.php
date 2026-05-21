<?php

declare(strict_types=1);

namespace App\Identity\Application\Port;

use App\Identity\Application\Dto\DeleteGroupInputDto;

/** Порт входящего Use Case удаления группы. */
interface DeleteGroupUseCaseInterface
{
    public function execute(DeleteGroupInputDto $input): void;
}
