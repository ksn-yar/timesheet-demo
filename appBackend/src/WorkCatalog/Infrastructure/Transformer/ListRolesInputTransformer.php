<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\ListRolesInputDto;
use App\WorkCatalog\Infrastructure\Dto\ListRolesRequestDto;

/** Трансформирует ListRolesRequestDto в ListRolesInputDto для Use Case. */
final readonly class ListRolesInputTransformer
{
    public function transform(ListRolesRequestDto $dto): ListRolesInputDto
    {
        return new ListRolesInputDto(
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
