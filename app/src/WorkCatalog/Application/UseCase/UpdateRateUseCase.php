<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\UpdateRateInputDto;
use App\WorkCatalog\Application\Port\RateAppliedToTicketCheckerInterface;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\RateNotFoundException;
use App\WorkCatalog\Domain\Exception\RoleNotFoundException;
use App\WorkCatalog\Domain\Exception\WorkNotFoundException;
use App\WorkCatalog\Domain\Repository\RateRepositoryInterface;
use App\WorkCatalog\Domain\Repository\RoleRepositoryInterface;
use App\WorkCatalog\Domain\Repository\WorkRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\Money;
use App\WorkCatalog\Domain\ValueObject\RateId;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления ставки. */
final class UpdateRateUseCase
{
    public function __construct(
        private readonly RateRepositoryInterface $rateRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly WorkRepositoryInterface $workRepository,
        private readonly RateAppliedToTicketCheckerInterface $rateAppliedChecker,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateRateInputDto $input): void
    {
        $rateId = new RateId($input->id);
        $rate = $this->rateRepository->findById($rateId);

        if (null === $rate) {
            throw new RateNotFoundException($input->id);
        }

        if ($rate->isDeleted()) {
            throw new EntityDeletedException();
        }

        $isApplied = $this->rateAppliedChecker->isRateAppliedToTicket($rateId);

        $newRoleId = $rate->getRoleId();

        if (null !== $input->roleId) {
            $newRoleId = new RoleId($input->roleId);
            $role = $this->roleRepository->findById($newRoleId);

            if (null === $role) {
                throw new RoleNotFoundException($input->roleId);
            }

            if ($role->isDeleted()) {
                throw new EntityDeletedException();
            }
        }

        $newWorkId = $rate->getWorkId();

        if (null !== $input->workId) {
            $newWorkId = new WorkId($input->workId);
            $work = $this->workRepository->findById($newWorkId);

            if (null === $work) {
                throw new WorkNotFoundException($input->workId);
            }

            if ($work->isDeleted()) {
                throw new EntityDeletedException();
            }
        }

        $newAmount = $input->amount ?? $rate->getMoney()->amount();
        $newCurrency = $input->currency ?? $rate->getMoney()->currency();
        $newMoney = new Money($newAmount, $newCurrency);

        $newEffectiveFrom = null !== $input->effectiveFrom
            ? new DateTimeImmutable($input->effectiveFrom)
            : $rate->getEffectiveFrom();

        $rate->update($newMoney, $newEffectiveFrom, $newRoleId, $newWorkId, $isApplied);

        $this->rateRepository->save($rate);

        foreach ($rate->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
