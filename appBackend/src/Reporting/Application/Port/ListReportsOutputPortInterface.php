<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

use App\Reporting\Application\Dto\ListReportsOutputDto;

/** Контракт представления результата Use Case получения списка отчётов. */
interface ListReportsOutputPortInterface
{
    public function present(ListReportsOutputDto $dto): void;
}
