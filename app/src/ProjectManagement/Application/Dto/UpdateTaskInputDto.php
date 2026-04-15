<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case обновления задачи. */
final readonly class UpdateTaskInputDto
{
    public function __construct(
        public string $id,
        public ?string $name = null,
        public mixed $description = '__NOT_SET__',
        public mixed $estimate = '__NOT_SET__',
    ) {}
}
