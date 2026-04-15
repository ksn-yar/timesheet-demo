<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\ListRatesInputDto;
use App\WorkCatalog\Infrastructure\Dto\ListRatesRequestDto;

/** Трансформирует ListRatesRequestDto в ListRatesInputDto для Use Case. */
final class ListRatesInputTransformer
{
    public function transform(ListRatesRequestDto $dto): ListRatesInputDto
    {
        return new ListRatesInputDto(
            roleId: $dto->roleId,
            workId: $dto->workId,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
