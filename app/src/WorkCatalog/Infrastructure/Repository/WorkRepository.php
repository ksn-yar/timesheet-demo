<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Repository;

use App\Persistence\Entity\Work as WorkOrmEntity;
use App\Persistence\Repository\WorkRepository as WorkOrmRepository;
use App\WorkCatalog\Domain\Entity\Work;
use App\WorkCatalog\Domain\Repository\WorkRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища Work.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class WorkRepository implements WorkRepositoryInterface
{
    public function __construct(
        private readonly WorkOrmRepository $ormRepository,
    ) {}

    public function save(Work $work): void
    {
        $existingOrmEntity = $this->ormRepository->find($work->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $work);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($work);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(WorkId $id): ?Work
    {
        $ormEntity = $this->ormRepository->find($id->value());

        if (null === $ormEntity) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    public function findByName(string $name): ?Work
    {
        $ormEntity = $this->ormRepository->findByName($name);

        if (null === $ormEntity) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    /** @return Work[] */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $ormEntities = $this->ormRepository->findActiveAll($criteria, $page, $perPage);

        return array_map(
            fn (WorkOrmEntity $ormEntity): Work => $this->toDomainEntity($ormEntity),
            $ormEntities,
        );
    }

    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countActive($criteria);
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(Work $work): WorkOrmEntity
    {
        $ormEntity = new WorkOrmEntity();
        $ormEntity->setId($work->getId()->value());
        $ormEntity->setName($work->getName());
        $ormEntity->setDescription($work->getDescription());
        $ormEntity->setDeletedAt($work->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(WorkOrmEntity $ormEntity, Work $work): void
    {
        $ormEntity->setName($work->getName());
        $ormEntity->setDescription($work->getDescription());
        $ormEntity->setDeletedAt($work->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(WorkOrmEntity $ormEntity): Work
    {
        return Work::restore(
            $ormEntity->getId(),
            $ormEntity->getName(),
            $ormEntity->getDescription(),
            $ormEntity->getDeletedAt(),
        );
    }
}
