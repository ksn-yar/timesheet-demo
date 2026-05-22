<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Repository;

use App\Persistence\Entity\Rate as RateOrmEntity;
use App\Persistence\Entity\Role as RoleOrmEntity;
use App\Persistence\Entity\Work as WorkOrmEntity;
use App\Persistence\Repository\RateRepository as RateOrmRepository;
use App\WorkCatalog\Domain\Entity\Rate;
use App\WorkCatalog\Domain\Repository\RateRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\Money;
use App\WorkCatalog\Domain\ValueObject\RateId;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища Rate.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class RateRepository implements RateRepositoryInterface
{
    public function __construct(
        private readonly RateOrmRepository $ormRepository,
    ) {}

    public function save(Rate $rate): void
    {
        $existingOrmEntity = $this->ormRepository->find($rate->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $rate);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($rate);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(RateId $id): ?Rate
    {
        $ormEntity = $this->ormRepository->find($id->value());

        if (null === $ormEntity) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Rate[]
     */
    public function findAll(array $criteria = [], int $limit = 1, int $offset = 20): array
    {
        $ormEntities = $this->ormRepository->findActiveAll($criteria, $limit, $offset);

        return array_map(
            fn (RateOrmEntity $ormEntity): Rate => $this->toDomainEntity($ormEntity),
            $ormEntities,
        );
    }

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countActive($criteria);
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(Rate $rate): RateOrmEntity
    {
        $ormEntity = new RateOrmEntity();
        $ormEntity->setId($rate->getId()->value());
        $ormEntity->setAmount($rate->getMoney()->amount());
        $ormEntity->setCurrency($rate->getMoney()->currency());
        $ormEntity->setEffectiveFrom($rate->getEffectiveFrom());
        $em = $this->ormRepository->getEntityManager();
        $roleId = $rate->getRoleId()?->value();
        $ormEntity->setRole(null !== $roleId ? $em->getReference(RoleOrmEntity::class, $roleId) : null);
        $workId = $rate->getWorkId()?->value();
        $ormEntity->setWork(null !== $workId ? $em->getReference(WorkOrmEntity::class, $workId) : null);
        $ormEntity->setDeletedAt($rate->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(RateOrmEntity $ormEntity, Rate $rate): void
    {
        $ormEntity->setAmount($rate->getMoney()->amount());
        $ormEntity->setCurrency($rate->getMoney()->currency());
        $ormEntity->setEffectiveFrom($rate->getEffectiveFrom());
        $em = $this->ormRepository->getEntityManager();
        $roleId = $rate->getRoleId()?->value();
        $ormEntity->setRole(null !== $roleId ? $em->getReference(RoleOrmEntity::class, $roleId) : null);
        $workId = $rate->getWorkId()?->value();
        $ormEntity->setWork(null !== $workId ? $em->getReference(WorkOrmEntity::class, $workId) : null);
        $ormEntity->setDeletedAt($rate->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(RateOrmEntity $ormEntity): Rate
    {
        $roleIdStr = $ormEntity->getRoleId();
        $workIdStr = $ormEntity->getWorkId();

        return Rate::restore(
            new RateId($ormEntity->getId()),
            new Money($ormEntity->getAmount(), $ormEntity->getCurrency()),
            $ormEntity->getEffectiveFrom(),
            null !== $roleIdStr ? new RoleId($roleIdStr) : null,
            null !== $workIdStr ? new WorkId($workIdStr) : null,
            $ormEntity->getDeletedAt(),
        );
    }
}
