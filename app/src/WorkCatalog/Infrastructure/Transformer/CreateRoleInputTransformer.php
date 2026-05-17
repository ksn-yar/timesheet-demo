<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Transformer;

use App\WorkCatalog\Application\Dto\CreateRoleInputDto;
use App\WorkCatalog\Infrastructure\Dto\CreateRoleRequestDto;

/** Трансформирует CreateRoleRequestDto в CreateRoleInputDto для Use Case. */
final readonly class CreateRoleInputTransformer
{
    public function transform(CreateRoleRequestDto $dto): CreateRoleInputDto
    {
        return new CreateRoleInputDto(
            name: $dto->name,
            description: $dto->description,
        );
    }
}
