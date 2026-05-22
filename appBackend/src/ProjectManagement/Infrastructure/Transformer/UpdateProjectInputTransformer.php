<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\UpdateProjectInputDto;
use App\ProjectManagement\Infrastructure\Dto\UpdateProjectRequestDto;

/** Трансформирует UpdateProjectRequestDto в UpdateProjectInputDto для Use Case. */
final readonly class UpdateProjectInputTransformer
{
    public function transform(string $id, UpdateProjectRequestDto $dto): UpdateProjectInputDto
    {
        return new UpdateProjectInputDto(
            id: $id,
            name: $dto->name,
            status: $dto->status,
            description: $dto->description,
        );
    }
}
