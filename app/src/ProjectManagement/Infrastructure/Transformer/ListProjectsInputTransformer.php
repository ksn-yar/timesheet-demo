<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\ListProjectsInputDto;
use App\ProjectManagement\Infrastructure\Dto\ListProjectsRequestDto;

/** Трансформирует ListProjectsRequestDto в ListProjectsInputDto для Use Case. */
final class ListProjectsInputTransformer
{
    public function transform(ListProjectsRequestDto $dto): ListProjectsInputDto
    {
        return new ListProjectsInputDto(
            clientId: $dto->clientId,
            status: $dto->status,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
