<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\CreateImportPolicyInputDto;
use App\Timesheet\Infrastructure\Dto\CreateImportPolicyRequestDto;

/** Трансформирует HTTP DTO создания политики импорта в Application DTO. */
final class CreateImportPolicyInputTransformer
{
    public function transform(CreateImportPolicyRequestDto $dto): CreateImportPolicyInputDto
    {
        return new CreateImportPolicyInputDto(
            name: $dto->name,
            sourceSystem: $dto->sourceSystem,
            mappingRules: $dto->mappingRules,
            allowEdit: $dto->allowEdit,
        );
    }
}
