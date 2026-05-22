<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\CreateProjectInputDto;
use App\ProjectManagement\Infrastructure\Dto\CreateProjectRequestDto;

/** Трансформирует CreateProjectRequestDto в CreateProjectInputDto для Use Case. */
final readonly class CreateProjectInputTransformer
{
    public function transform(CreateProjectRequestDto $dto): CreateProjectInputDto
    {
        return new CreateProjectInputDto(
            clientId: $dto->clientId,
            name: $dto->name,
            status: $dto->status,
            description: $dto->description,
        );
    }
}
