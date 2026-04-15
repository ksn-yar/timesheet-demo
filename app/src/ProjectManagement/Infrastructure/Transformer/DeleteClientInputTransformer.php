<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\DeleteClientInputDto;

/** Трансформирует path-параметр id в DeleteClientInputDto для Use Case. */
final class DeleteClientInputTransformer
{
    public function transform(string $id): DeleteClientInputDto
    {
        return new DeleteClientInputDto(id: $id);
    }
}
