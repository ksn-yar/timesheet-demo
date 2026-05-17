<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Transformer;

use App\Identity\Application\Dto\ListGroupsInputDto;
use App\Identity\Infrastructure\Dto\ListGroupsRequestDto;

/** Трансформирует ListGroupsRequestDto в ListGroupsInputDto для Use Case. */
final readonly class ListGroupsInputTransformer
{
    public function transform(ListGroupsRequestDto $dto): ListGroupsInputDto
    {
        return new ListGroupsInputDto(
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
