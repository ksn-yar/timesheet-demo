<?php

declare(strict_types=1);

namespace App\Identity\Application\Dto;

/** Входные данные Use Case обновления группы. */
final readonly class UpdateGroupInputDto
{
    public function __construct(
        public string $groupId,
        public string $name,
        public ?string $description = null,
    ) {}
}
