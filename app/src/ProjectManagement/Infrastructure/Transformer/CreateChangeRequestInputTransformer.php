<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\CreateChangeRequestInputDto;
use App\ProjectManagement\Infrastructure\Dto\CreateChangeRequestRequestDto;

/** Трансформирует CreateChangeRequestRequestDto в CreateChangeRequestInputDto для Use Case. */
final readonly class CreateChangeRequestInputTransformer
{
    public function transform(CreateChangeRequestRequestDto $dto): CreateChangeRequestInputDto
    {
        return new CreateChangeRequestInputDto(
            projectId: $dto->projectId,
            name: $dto->name,
            description: $dto->description,
        );
    }
}
