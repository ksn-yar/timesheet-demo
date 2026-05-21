<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Presenter;

use App\Timesheet\Application\Dto\ListImportPoliciesOutputDto;
use App\Timesheet\Application\Port\ListImportPoliciesOutputPortInterface;
use App\Timesheet\Infrastructure\Dto\ImportPolicyListResponseDto;
use App\Timesheet\Infrastructure\Dto\ImportPolicyResponseDto;
use LogicException;

/** HTTP-презентер результата Use Case получения списка политик импорта. Формирует Response DTO. */
final class HttpListImportPoliciesPresenter implements ListImportPoliciesOutputPortInterface
{
    private ?ListImportPoliciesOutputDto $dto = null;

    public function present(ListImportPoliciesOutputDto $dto): void
    {
        $this->dto = $dto;
    }

    public function getResponseDto(): ImportPolicyListResponseDto
    {
        if (null === $this->dto) {
            throw new LogicException('Presenter has not been called yet.');
        }

        $items = array_map(
            static fn ($item) => new ImportPolicyResponseDto(
                id: $item->id,
                name: $item->name,
                sourceSystem: $item->sourceSystem,
                mappingRules: $item->mappingRules,
                allowEdit: $item->allowEdit,
                isActive: $item->isActive,
            ),
            $this->dto->items,
        );

        return new ImportPolicyListResponseDto(
            items: $items,
            total: $this->dto->total,
            page: $this->dto->page,
            perPage: $this->dto->perPage,
        );
    }
}
