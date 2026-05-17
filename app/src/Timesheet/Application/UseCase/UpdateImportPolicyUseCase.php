<?php

declare(strict_types=1);

namespace App\Timesheet\Application\UseCase;

use App\Timesheet\Application\Dto\UpdateImportPolicyInputDto;
use App\Timesheet\Domain\Exception\ImportPolicyConflictException;
use App\Timesheet\Domain\Exception\ImportPolicyNotFoundException;
use App\Timesheet\Domain\Repository\ImportPolicyRepositoryInterface;
use App\Timesheet\Domain\ValueObject\ImportPolicyId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления политики импорта с проверкой конфликтов активации. */
final readonly class UpdateImportPolicyUseCase
{
    public function __construct(
        private ImportPolicyRepositoryInterface $importPolicyRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateImportPolicyInputDto $input): void
    {
        $policy = $this->importPolicyRepository->findById(new ImportPolicyId($input->importPolicyId));

        if (null === $policy) {
            throw new ImportPolicyNotFoundException($input->importPolicyId);
        }

        if (true === $input->isActive) {
            $existing = $this->importPolicyRepository->findActiveBySourceSystem($policy->getSourceSystem());

            if (null !== $existing && !$existing->getId()->equals($policy->getId())) {
                throw new ImportPolicyConflictException($policy->getSourceSystem());
            }
        }

        $policy->update($input->mappingRules, $input->allowEdit, $input->isActive);

        $this->importPolicyRepository->save($policy);

        foreach ($policy->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
