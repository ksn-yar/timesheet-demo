<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Repository;

use App\Persistence\Entity\Role as RoleOrmEntity;
use App\Persistence\Repository\RoleRepository as RoleOrmRepository;
use App\WorkCatalog\Domain\Entity\Role;
use App\WorkCatalog\Domain\Repository\RoleRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища Role.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class RoleRepository implements RoleRepositoryInterface
{
    public function __construct(
        private readonly RoleOrmRepository $ormRepository,
    ) {}

    public function save(Role $role): void
    {
        $existingOrmEntity = $this->ormRepository->find($role->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $role);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($role);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(RoleId $id): ?Role
    {
        $ormEntity = $this->ormRepository->find($id->value());

        if (null === $ormEntity) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    public function findByName(string $name): ?Role
    {
        $ormEntity = $this->ormRepository->findByName($name);

        if (null === $ormEntity) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Role[]
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $ormEntities = $this->ormRepository->findActiveAll($criteria, $page, $perPage);

        return array_map(
            fn (RoleOrmEntity $ormEntity): Role => $this->toDomainEntity($ormEntity),
            $ormEntities,
        );
    }

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countActive($criteria);
    }

    public function countActiveRatesByRoleId(RoleId $roleId): int
    {
        return $this->ormRepository->countActiveRatesByRoleId($roleId->value());
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(Role $role): RoleOrmEntity
    {
        $ormEntity = new RoleOrmEntity();
        $ormEntity->setId($role->getId()->value());
        $ormEntity->setName($role->getName());
        $ormEntity->setDescription($role->getDescription());
        $ormEntity->setDeletedAt($role->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(RoleOrmEntity $ormEntity, Role $role): void
    {
        $ormEntity->setName($role->getName());
        $ormEntity->setDescription($role->getDescription());
        $ormEntity->setDeletedAt($role->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(RoleOrmEntity $ormEntity): Role
    {
        return Role::restore(
            new RoleId($ormEntity->getId()),
            $ormEntity->getName(),
            $ormEntity->getDescription(),
            $ormEntity->getDeletedAt(),
        );
    }
}
