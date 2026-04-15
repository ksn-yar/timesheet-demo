<?php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\ImportPolicyItemDto;
use App\Timesheet\Application\Dto\ListImportPoliciesInputDto;
use App\Timesheet\Application\Dto\ListImportPoliciesOutputDto;
use App\Timesheet\Application\Port\ListImportPoliciesOutputPortInterface;
use App\Timesheet\Domain\Entity\ImportPolicy;
use App\Timesheet\Domain\Repository\ImportPolicyRepositoryInterface;

/** Use Case получения списка политик импорта с пагинацией. */
final class ListImportPoliciesUseCase
{
    public function __construct(
        private readonly ImportPolicyRepositoryInterface $importPolicyRepository,
        private readonly ListImportPoliciesOutputPortInterface $presenter,
    ) {}

    public function execute(ListImportPoliciesInputDto $input): void
    {
        $policies = $this->importPolicyRepository->findAll([], $input->page, $input->perPage);
        $total = $this->importPolicyRepository->countAll();

        $items = array_map(
            static fn (ImportPolicy $policy): ImportPolicyItemDto => new ImportPolicyItemDto(
                id: $policy->getId()->value(),
                name: $policy->getName(),
                sourceSystem: $policy->getSourceSystem(),
                mappingRules: $policy->getMappingRules(),
                allowEdit: $policy->isAllowEdit(),
                isActive: $policy->isActive(),
            ),
            $policies,
        );

        $this->presenter->present(new ListImportPoliciesOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }
}
