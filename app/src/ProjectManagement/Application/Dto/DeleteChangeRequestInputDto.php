<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case удаления запроса на изменение. */
final readonly class DeleteChangeRequestInputDto
{
    public function __construct(
        public string $id,
    ) {}
}
