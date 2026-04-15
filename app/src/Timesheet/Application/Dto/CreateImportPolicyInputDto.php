<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Входные данные Use Case создания политики импорта. */
final readonly class CreateImportPolicyInputDto
{
    public function __construct(
        public string $name,
        public string $sourceSystem,
        public array $mappingRules,
        public bool $allowEdit,
    ) {}
}
