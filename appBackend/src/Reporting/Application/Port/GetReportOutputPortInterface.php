<?php

declare(strict_types=1);

namespace App\Reporting\Application\Port;

use App\Reporting\Application\Dto\GetReportOutputDto;

/** Контракт представления результата Use Case получения отчёта. */
interface GetReportOutputPortInterface
{
    public function present(GetReportOutputDto $dto): void;
}
