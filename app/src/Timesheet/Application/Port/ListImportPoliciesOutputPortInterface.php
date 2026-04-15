<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

use App\Timesheet\Application\Dto\ListImportPoliciesOutputDto;

/** Контракт представления результата Use Case получения списка политик импорта. */
interface ListImportPoliciesOutputPortInterface
{
    public function present(ListImportPoliciesOutputDto $dto): void;
}
