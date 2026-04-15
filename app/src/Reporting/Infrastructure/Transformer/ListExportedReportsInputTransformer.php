<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Transformer;

use App\Reporting\Application\Dto\ListExportedReportsInputDto;
use App\Reporting\Infrastructure\Dto\ListExportedReportsRequestDto;

/** Трансформирует ListExportedReportsRequestDto в ListExportedReportsInputDto для Use Case. */
final class ListExportedReportsInputTransformer
{
    public function transform(ListExportedReportsRequestDto $dto): ListExportedReportsInputDto
    {
        return new ListExportedReportsInputDto(
            format: $dto->format,
            generatedAtFrom: $dto->generatedAtFrom,
            generatedAtTo: $dto->generatedAtTo,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
