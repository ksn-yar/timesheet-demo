<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\CreateTaskInputDto;
use App\ProjectManagement\Infrastructure\Dto\CreateTaskRequestDto;

/** Трансформирует CreateTaskRequestDto в CreateTaskInputDto для Use Case. */
final class CreateTaskInputTransformer
{
    public function transform(CreateTaskRequestDto $dto): CreateTaskInputDto
    {
        return new CreateTaskInputDto(
            projectId: $dto->projectId,
            crId: $dto->crId,
            name: $dto->name,
            description: $dto->description,
            estimate: $dto->estimate,
        );
    }
}
