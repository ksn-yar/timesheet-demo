<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\UpdateImportPolicyInputDto;
use App\Timesheet\Infrastructure\Dto\UpdateImportPolicyRequestDto;

/** Трансформирует HTTP DTO обновления политики импорта в Application DTO. */
final class UpdateImportPolicyInputTransformer
{
    public function transform(string $importPolicyId, UpdateImportPolicyRequestDto $dto): UpdateImportPolicyInputDto
    {
        return new UpdateImportPolicyInputDto(
            importPolicyId: $importPolicyId,
            mappingRules: $dto->mappingRules,
            allowEdit: $dto->allowEdit,
            isActive: $dto->isActive,
        );
    }
}
