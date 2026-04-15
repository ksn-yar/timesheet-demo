<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Transformer;

use App\ProjectManagement\Application\Dto\DeleteTaskInputDto;

/** Трансформирует path-параметр id в DeleteTaskInputDto для Use Case. */
final class DeleteTaskInputTransformer
{
    public function transform(string $id): DeleteTaskInputDto
    {
        return new DeleteTaskInputDto(id: $id);
    }
}
