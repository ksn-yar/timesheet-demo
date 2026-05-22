<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case удаления задачи. */
final readonly class DeleteTaskInputDto
{
    public function __construct(
        public string $id,
    ) {}
}
