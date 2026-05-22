<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Выходные данные Use Case получения отчёта: полное представление включая строки данных. */
final readonly class GetReportOutputDto
{
    /**
     * @param null|array<string, mixed>[]      $filters Применённые фильтры
     * @param string[]                         $groupBy Значения GroupByDimension
     * @param array<int, array<string, mixed>> $data    Строки агрегированных данных
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $createdBy,
        public string $createdAt,
        public string $periodFrom,
        public string $periodTo,
        public ?array $filters,
        public array $groupBy,
        public array $data,
    ) {}
}
