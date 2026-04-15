<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\UpdateClientInputDto;
use App\ProjectManagement\Infrastructure\Dto\UpdateClientRequestDto;

/** Трансформирует UpdateClientRequestDto в UpdateClientInputDto для Use Case. */
final class UpdateClientInputTransformer
{
    public function transform(string $id, UpdateClientRequestDto $dto): UpdateClientInputDto
    {
        return new UpdateClientInputDto(
            id: $id,
            name: $dto->name,
            description: $dto->description,
        );
    }
}
