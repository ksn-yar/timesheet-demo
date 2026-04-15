<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Repository;

use App\Persistence\Entity\ImportPolicy as ImportPolicyOrmEntity;
use App\Persistence\Repository\ImportPolicyRepository as ImportPolicyOrmRepository;
use App\Timesheet\Domain\Entity\ImportPolicy;
use App\Timesheet\Domain\Repository\ImportPolicyRepositoryInterface;
use App\Timesheet\Domain\ValueObject\ImportPolicyId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища ImportPolicy.
 * Использует composition с Doctrine Repository из Persistence-слоя.
 */
final class ImportPolicyRepository implements ImportPolicyRepositoryInterface
{
    public function __construct(
        private readonly ImportPolicyOrmRepository $ormRepository,
    ) {}

    public function save(ImportPolicy $policy): void
    {
        $existing = $this->ormRepository->find($policy->getId()->value());

        if (null !== $existing) {
            $this->updateOrmEntity($existing, $policy);
            $this->ormRepository->save($existing, flush: true);

            return;
        }

        $this->ormRepository->save($this->toOrmEntity($policy), flush: true);
    }

    public function findById(ImportPolicyId $id): ?ImportPolicy
    {
        $orm = $this->ormRepository->find($id->value());

        return null !== $orm ? $this->toDomainEntity($orm) : null;
    }

    /** @return ImportPolicy[] */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        return array_map(
            fn (ImportPolicyOrmEntity $orm): ImportPolicy => $this->toDomainEntity($orm),
            $this->ormRepository->findAllPaginated($criteria, $page, $perPage),
        );
    }

    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countAll($criteria);
    }

    public function findActiveBySourceSystem(string $sourceSystem): ?ImportPolicy
    {
        $orm = $this->ormRepository->findActiveBySourceSystem($sourceSystem);

        return null !== $orm ? $this->toDomainEntity($orm) : null;
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(ImportPolicy $policy): ImportPolicyOrmEntity
    {
        $orm = new ImportPolicyOrmEntity();
        $orm->setId($policy->getId()->value());
        $orm->setName($policy->getName());
        $orm->setSourceSystem($policy->getSourceSystem());
        $orm->setMappingRules($policy->getMappingRules());
        $orm->setAllowEdit($policy->isAllowEdit());
        $orm->setIsActive($policy->isActive());
        $orm->setCreatedAt(new DateTimeImmutable());

        return $orm;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(ImportPolicyOrmEntity $orm, ImportPolicy $policy): void
    {
        $orm->setName($policy->getName());
        $orm->setMappingRules($policy->getMappingRules());
        $orm->setAllowEdit($policy->isAllowEdit());
        $orm->setIsActive($policy->isActive());
        $orm->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(ImportPolicyOrmEntity $orm): ImportPolicy
    {
        return ImportPolicy::restore(
            $orm->getId(),
            $orm->getName(),
            $orm->getSourceSystem(),
            $orm->getMappingRules(),
            $orm->isAllowEdit(),
            $orm->isActive(),
        );
    }
}
