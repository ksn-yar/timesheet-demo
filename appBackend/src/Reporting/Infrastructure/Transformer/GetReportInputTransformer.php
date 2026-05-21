<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Transformer;

use App\Reporting\Application\Dto\GetReportInputDto;

/** Трансформирует строковый ID из параметра маршрута в GetReportInputDto. */
final readonly class GetReportInputTransformer
{
    public function transform(string $id): GetReportInputDto
    {
        return new GetReportInputDto(reportId: $id);
    }
}
