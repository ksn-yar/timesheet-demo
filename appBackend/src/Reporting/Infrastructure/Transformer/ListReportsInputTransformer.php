<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Transformer;

use App\Reporting\Application\Dto\ListReportsInputDto;
use App\Reporting\Infrastructure\Dto\ListReportsRequestDto;

/** Трансформирует ListReportsRequestDto в ListReportsInputDto для Use Case. */
final readonly class ListReportsInputTransformer
{
    public function transform(ListReportsRequestDto $dto): ListReportsInputDto
    {
        return new ListReportsInputDto(
            createdBy: $dto->createdBy,
            periodFrom: $dto->periodFrom,
            periodTo: $dto->periodTo,
            name: $dto->name,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
