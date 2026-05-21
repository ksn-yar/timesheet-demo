<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Port;

use App\ProjectManagement\Application\Dto\ListProjectsOutputDto;

/** Контракт представления результата Use Case получения списка проектов. */
interface ListProjectsOutputPortInterface
{
    public function present(ListProjectsOutputDto $dto): void;
}
