<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\CreateWorkInputDto;
use App\WorkCatalog\Infrastructure\Dto\CreateWorkRequestDto;

/** Трансформирует CreateWorkRequestDto в CreateWorkInputDto для Use Case. */
final readonly class CreateWorkInputTransformer
{
    public function transform(CreateWorkRequestDto $dto): CreateWorkInputDto
    {
        return new CreateWorkInputDto(
            name: $dto->name,
            description: $dto->description,
        );
    }
}
