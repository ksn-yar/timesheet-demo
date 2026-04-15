<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Repository;

use App\Reporting\Domain\Entity\Report;
use App\Reporting\Domain\ValueObject\ReportId;

/** Контракт хранилища отчётов. Реализуется в Infrastructure-слое. */
interface ReportRepositoryInterface
{
    public function save(Report $report): void;

    public function findById(ReportId $id): ?Report;

    public function findAll(array $criteria): array;

    public function count(array $criteria): int;
}
