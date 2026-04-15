<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Transformer;

use App\Timesheet\Application\Dto\ListImportPoliciesInputDto;
use App\Timesheet\Infrastructure\Dto\ListImportPoliciesRequestDto;

/** Трансформирует HTTP DTO списка политик импорта в Application DTO. */
final class ListImportPoliciesInputTransformer
{
    public function transform(ListImportPoliciesRequestDto $dto): ListImportPoliciesInputDto
    {
        return new ListImportPoliciesInputDto(
            page: $dto->page,
            perPage: $dto->perPage,
        );
    }
}
