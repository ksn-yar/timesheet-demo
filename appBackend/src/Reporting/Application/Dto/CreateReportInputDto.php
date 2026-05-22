<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Входные данные Use Case создания отчёта. UUID генерируется в InputTransformer до вызова Use Case. */
final readonly class CreateReportInputDto
{
    /**
     * @param null|array<string, array<string>> $filters Фильтры в виде ['employeeIds' => [...], ...]
     * @param string[]                          $groupBy Значения GroupByDimension
     */
    public function __construct(
        public string $reportId,
        public string $name,
        public string $createdBy,
        public string $periodFrom,
        public string $periodTo,
        public ?array $filters = null,
        public array $groupBy = [],
    ) {}
}
