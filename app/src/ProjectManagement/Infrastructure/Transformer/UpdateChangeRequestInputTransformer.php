<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\UpdateChangeRequestInputDto;
use App\ProjectManagement\Infrastructure\Dto\UpdateChangeRequestRequestDto;

/** Трансформирует UpdateChangeRequestRequestDto в UpdateChangeRequestInputDto для Use Case. */
final readonly class UpdateChangeRequestInputTransformer
{
    public function transform(string $id, UpdateChangeRequestRequestDto $dto): UpdateChangeRequestInputDto
    {
        return new UpdateChangeRequestInputDto(
            id: $id,
            name: $dto->name,
            description: $dto->description,
        );
    }
}
