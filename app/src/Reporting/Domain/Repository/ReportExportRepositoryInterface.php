<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\ValueObject\ReportExport;
use App\Reporting\Domain\ValueObject\ReportExportId;

/** Контракт хранилища выгрузок отчётов. Реализуется в Infrastructure-слое. */
interface ReportExportRepositoryInterface
{
    public function save(ReportExport $export): void;

    public function findById(ReportExportId $id): ?ReportExport;

    public function findAll(array $criteria): array;

    public function count(array $criteria): int;
}
