<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\ListChangeRequestsInputDto;
use App\ProjectManagement\Infrastructure\Dto\ListChangeRequestsRequestDto;

/** Трансформирует ListChangeRequestsRequestDto в ListChangeRequestsInputDto для Use Case. */
final readonly class ListChangeRequestsInputTransformer
{
    public function transform(ListChangeRequestsRequestDto $dto): ListChangeRequestsInputDto
    {
        return new ListChangeRequestsInputDto(
            projectId: $dto->projectId,
            name: $dto->name,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
