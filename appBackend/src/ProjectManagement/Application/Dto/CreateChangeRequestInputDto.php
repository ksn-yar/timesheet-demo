<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\Dto;

/** Входные данные Use Case создания запроса на изменение. */
final readonly class CreateChangeRequestInputDto
{
    public function __construct(
        public string $projectId,
        public string $name,
        public ?string $description = null,
    ) {}
}
