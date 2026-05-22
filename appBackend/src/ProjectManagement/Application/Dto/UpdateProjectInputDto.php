<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case обновления проекта. */
final readonly class UpdateProjectInputDto
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public ?string $status = null,
        public mixed $description = '__NOT_SET__',
    ) {}
}
