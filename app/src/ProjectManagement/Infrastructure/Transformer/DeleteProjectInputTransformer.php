<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\DeleteProjectInputDto;

/** Трансформирует path-параметр id в DeleteProjectInputDto для Use Case. */
final class DeleteProjectInputTransformer
{
    public function transform(string $id): DeleteProjectInputDto
    {
        return new DeleteProjectInputDto(id: $id);
    }
}
