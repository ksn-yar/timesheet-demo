<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\ListRatesInputDto;
use App\WorkCatalog\Application\Dto\ListRatesOutputDto;
use App\WorkCatalog\Application\Dto\RateItemDto;
use App\WorkCatalog\Application\Port\ListRatesOutputPortInterface;
use App\WorkCatalog\Domain\Entity\Rate;
use App\WorkCatalog\Domain\Repository\RateRepositoryInterface;
use App\WorkCatalog\Domain\Repository\RoleRepositoryInterface;
use App\WorkCatalog\Domain\Repository\WorkRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use App\WorkCatalog\Domain\ValueObject\WorkId;

/** Use Case получения списка ставок с пагинацией и фильтрацией. */
final class ListRatesUseCase
{
    public function __construct(
        private readonly RateRepositoryInterface $rateRepository,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly WorkRepositoryInterface $workRepository,
        private readonly ListRatesOutputPortInterface $presenter,
    ) {}

    public function execute(ListRatesInputDto $input): void
    {
        $criteria = [];

        if (null !== $input->roleId) {
            $criteria['roleId'] = $input->roleId;
        }

        if (null !== $input->workId) {
            $criteria['workId'] = $input->workId;
        }

        $offset = ($input->page - 1) * $input->perPage;

        $rates = $this->rateRepository->findAll($criteria, $input->perPage, $offset);
        $total = $this->rateRepository->countAll($criteria);

        $roleNames = $this->resolveRoleNames($rates);
        $workNames = $this->resolveWorkNames($rates);

        $items = array_map(
            static fn (Rate $rate): RateItemDto => new RateItemDto(
                id: $rate->getId()->value(),
                amount: $rate->getMoney()->amount(),
                currency: $rate->getMoney()->currency(),
                effectiveFrom: $rate->getEffectiveFrom()->format('Y-m-d'),
                roleId: $rate->getRoleId()?->value(),
                roleName: null !== $rate->getRoleId()
                    ? ($roleNames[$rate->getRoleId()->value()] ?? null)
                    : null,
                workId: $rate->getWorkId()?->value(),
                workName: null !== $rate->getWorkId()
                    ? ($workNames[$rate->getWorkId()->value()] ?? null)
                    : null,
            ),
            $rates,
        );

        $this->presenter->present(new ListRatesOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }

    /**
     * Собирает имена ролей для списка ставок одним проходом.
     *
     * @param Rate[] $rates
     *
     * @return array<string, string>
     */
    private function resolveRoleNames(array $rates): array
    {
        $roleIds = [];

        foreach ($rates as $rate) {
            if (null !== $rate->getRoleId()) {
                $roleIds[$rate->getRoleId()->value()] = true;
            }
        }

        $names = [];

        foreach (array_keys($roleIds) as $roleId) {
            $role = $this->roleRepository->findById(new RoleId($roleId));

            if (null !== $role) {
                $names[$roleId] = $role->getName();
            }
        }

        return $names;
    }

    /**
     * Собирает имена видов работ для списка ставок одним проходом.
     *
     * @param Rate[] $rates
     *
     * @return array<string, string>
     */
    private function resolveWorkNames(array $rates): array
    {
        $workIds = [];

        foreach ($rates as $rate) {
            if (null !== $rate->getWorkId()) {
                $workIds[$rate->getWorkId()->value()] = true;
            }
        }

        $names = [];

        foreach (array_keys($workIds) as $workId) {
            $work = $this->workRepository->findById(new WorkId($workId));

            if (null !== $work) {
                $names[$workId] = $work->getName();
            }
        }

        return $names;
    }
}
