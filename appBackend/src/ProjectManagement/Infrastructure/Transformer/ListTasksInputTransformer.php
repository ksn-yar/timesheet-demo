<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\ListTasksInputDto;
use App\ProjectManagement\Infrastructure\Dto\ListTasksRequestDto;

/** Трансформирует ListTasksRequestDto в ListTasksInputDto для Use Case. */
final readonly class ListTasksInputTransformer
{
    public function transform(ListTasksRequestDto $dto): ListTasksInputDto
    {
        return new ListTasksInputDto(
            projectId: $dto->projectId,
            crId: $dto->crId,
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
