<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\Port;

use App\WorkCatalog\Application\Dto\ListRatesOutputDto;

/** Контракт представления результата Use Case получения списка ставок. */
interface ListRatesOutputPortInterface
{
    public function present(ListRatesOutputDto $dto): void;
}
