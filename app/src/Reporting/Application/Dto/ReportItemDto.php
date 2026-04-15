<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Элемент списка отчётов. Не содержит строк данных — только метаданные отчёта. */
final readonly class ReportItemDto
{
    /**
     * @param string[] $groupBy Значения GroupByDimension
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $createdBy,
        public string $createdAt,
        public string $periodFrom,
        public string $periodTo,
        public array $groupBy,
    ) {}
}
