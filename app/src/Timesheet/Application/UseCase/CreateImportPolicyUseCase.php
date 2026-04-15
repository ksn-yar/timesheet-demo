<?php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\CreateImportPolicyInputDto;
use App\Timesheet\Domain\Entity\ImportPolicy;
use App\Timesheet\Domain\Repository\ImportPolicyRepositoryInterface;
use App\Timesheet\Domain\ValueObject\ImportPolicyId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания новой политики импорта тикетов. */
final class CreateImportPolicyUseCase
{
    public function __construct(
        private readonly ImportPolicyRepositoryInterface $importPolicyRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateImportPolicyInputDto $input): void
    {
        $policy = ImportPolicy::create(
            ImportPolicyId::generate(),
            $input->name,
            $input->sourceSystem,
            $input->mappingRules,
            $input->allowEdit,
        );

        $this->importPolicyRepository->save($policy);

        foreach ($policy->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
