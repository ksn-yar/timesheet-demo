<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\UpdateTaskInputDto;
use App\ProjectManagement\Infrastructure\Dto\UpdateTaskRequestDto;

/** Трансформирует UpdateTaskRequestDto в UpdateTaskInputDto для Use Case. */
final class UpdateTaskInputTransformer
{
    public function transform(string $id, UpdateTaskRequestDto $dto): UpdateTaskInputDto
    {
        return new UpdateTaskInputDto(
            id: $id,
            name: $dto->name,
            description: $dto->description,
            estimate: $dto->estimate,
        );
    }
}
