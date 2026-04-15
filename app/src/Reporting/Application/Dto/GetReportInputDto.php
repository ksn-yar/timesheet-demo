<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Входные данные Use Case получения отчёта по идентификатору. */
final readonly class GetReportInputDto
{
    public function __construct(public string $reportId) {}
}
