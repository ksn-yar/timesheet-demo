<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\CreateRateInputDto;
use App\WorkCatalog\Domain\Entity\Rate;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\RoleNotFoundException;
use App\WorkCatalog\Domain\Exception\WorkNotFoundException;
use App\WorkCatalog\Domain\Repository\RateRepositoryInterface;
use App\WorkCatalog\Domain\Repository\RoleRepositoryInterface;
use App\WorkCatalog\Domain\Repository\WorkRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\Money;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use DateTimeImmutable;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания новой ставки. */
final class CreateRateUseCase
{
    public function __construct(
        private readonly RateRepositoryInterface $rateRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly WorkRepositoryInterface $workRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateRateInputDto $input): void
    {
        $roleId = null;
        $workId = null;

        if (null !== $input->roleId) {
            $roleId = new RoleId($input->roleId);
            $role = $this->roleRepository->findById($roleId);

            if (null === $role) {
                throw new RoleNotFoundException($input->roleId);
            }

            if ($role->isDeleted()) {
                throw new EntityDeletedException();
            }
        }

        if (null !== $input->workId) {
            $workId = new WorkId($input->workId);
            $work = $this->workRepository->findById($workId);

            if (null === $work) {
                throw new WorkNotFoundException($input->workId);
            }

            if ($work->isDeleted()) {
                throw new EntityDeletedException();
            }
        }

        $money = new Money($input->amount, $input->currency);
        $effectiveFrom = new DateTimeImmutable($input->effectiveFrom);

        $rate = Rate::create($money, $effectiveFrom, $roleId, $workId);

        $this->rateRepository->save($rate);

        foreach ($rate->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
