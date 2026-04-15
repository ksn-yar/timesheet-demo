<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Transformer;

use App\Reporting\Application\Dto\ExportReportsInputDto;
use App\Reporting\Domain\ValueObject\ReportExportId;
use App\Reporting\Infrastructure\Dto\ExportReportsRequestDto;

/** Трансформирует ExportReportsRequestDto в ExportReportsInputDto, генерируя UUID выгрузки. */
final class ExportReportsInputTransformer
{
    public function transform(ExportReportsRequestDto $dto): ExportReportsInputDto
    {
        return new ExportReportsInputDto(
            exportId: ReportExportId::generate()->value(),
            reportIds: $dto->reportIds,
            format: $dto->format,
        );
    }
}
