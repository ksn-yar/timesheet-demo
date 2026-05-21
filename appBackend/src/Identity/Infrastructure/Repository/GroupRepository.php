<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Repository;

use App\Identity\Domain\Entity\Group;
use App\Identity\Domain\Repository\GroupRepositoryInterface;
use App\Identity\Domain\ValueObject\GroupId;
use App\Persistence\Entity\Group as GroupOrmEntity;
use App\Persistence\Repository\GroupRepository as GroupOrmRepository;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища Group.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class GroupRepository implements GroupRepositoryInterface
{
    public function __construct(
        private readonly GroupOrmRepository $ormRepository,
    ) {}

    public function save(Group $group): void
    {
        $existingOrmEntity = $this->ormRepository->find($group->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $group);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($group);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(GroupId $id): ?Group
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
     * @return array{items: Group[], total: int}
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $ormEntities = $this->ormRepository->findAllPaginated($criteria, $page, $perPage);
        $total = $this->ormRepository->countAll($criteria);

        return [
            'items' => array_map(
                fn (GroupOrmEntity $ormEntity): Group => $this->toDomainEntity($ormEntity),
                $ormEntities,
            ),
            'total' => $total,
        ];
    }

    public function existsByName(string $name): bool
    {
        return $this->ormRepository->existsByName($name);
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(Group $group): GroupOrmEntity
    {
        $ormEntity = new GroupOrmEntity();
        $ormEntity->setId($group->getId()->value());
        $ormEntity->setName($group->getName());
        $ormEntity->setDescription($group->getDescription());
        $ormEntity->setDeletedAt($group->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(GroupOrmEntity $ormEntity, Group $group): void
    {
        $ormEntity->setName($group->getName());
        $ormEntity->setDescription($group->getDescription());
        $ormEntity->setDeletedAt($group->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(GroupOrmEntity $ormEntity): Group
    {
        return Group::restore(
            new GroupId($ormEntity->getId()),
            $ormEntity->getName(),
            $ormEntity->getDescription(),
            $ormEntity->getDeletedAt(),
        );
    }
}
