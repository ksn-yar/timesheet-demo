<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Dto;

/** Элемент списка политик импорта в выходных данных Use Case. */
final readonly class ImportPolicyItemDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $sourceSystem,
        public array $mappingRules,
        public bool $allowEdit,
        public bool $isActive,
    ) {}
}
