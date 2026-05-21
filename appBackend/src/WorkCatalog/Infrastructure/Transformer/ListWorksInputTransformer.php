<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\ListWorksInputDto;
use App\WorkCatalog\Infrastructure\Dto\ListWorksRequestDto;

/** Трансформирует ListWorksRequestDto в ListWorksInputDto для Use Case. */
final readonly class ListWorksInputTransformer
{
    public function transform(ListWorksRequestDto $dto): ListWorksInputDto
    {
        return new ListWorksInputDto(
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
