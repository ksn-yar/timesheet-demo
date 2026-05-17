<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\CreateClientInputDto;
use App\ProjectManagement\Infrastructure\Dto\CreateClientRequestDto;

/** Трансформирует CreateClientRequestDto в CreateClientInputDto для Use Case. */
final readonly class CreateClientInputTransformer
{
    public function transform(CreateClientRequestDto $dto): CreateClientInputDto
    {
        return new CreateClientInputDto(
            name: $dto->name,
            description: $dto->description,
        );
    }
}
