<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

use App\Reporting\Application\Dto\ListExportedReportsOutputDto;

/** Контракт представления результата Use Case получения списка выгрузок. */
interface ListExportedReportsOutputPortInterface
{
    public function present(ListExportedReportsOutputDto $dto): void;
}
