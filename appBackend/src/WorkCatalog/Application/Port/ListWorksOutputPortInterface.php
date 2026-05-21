<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Port;

use App\WorkCatalog\Application\Dto\ListWorksOutputDto;

/** Контракт представления результата Use Case получения списка видов работ. */
interface ListWorksOutputPortInterface
{
    public function present(ListWorksOutputDto $dto): void;
}
