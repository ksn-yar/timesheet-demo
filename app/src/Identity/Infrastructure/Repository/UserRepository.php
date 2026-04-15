<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Repository;

use App\Identity\Application\Port\TicketExistenceCheckerInterface;
use App\Identity\Domain\Entity\User;
use App\Identity\Domain\Enum\SystemRole;
use App\Identity\Domain\Repository\UserRepositoryInterface;
use App\Identity\Domain\ValueObject\Email;
use App\Identity\Domain\ValueObject\GroupId;
use App\Identity\Domain\ValueObject\HashedPassword;
use App\Identity\Domain\ValueObject\UserId;
use App\Persistence\Entity\User as UserOrmEntity;
use App\Persistence\Repository\UserRepository as UserOrmRepository;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища User.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class UserRepository implements UserRepositoryInterface, TicketExistenceCheckerInterface
{
    public function __construct(
        private readonly UserOrmRepository $ormRepository,
    ) {}

    public function save(User $user): void
    {
        $existingOrmEntity = $this->ormRepository->find($user->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $user);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($user);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(UserId $id): ?User
    {
        $ormEntity = $this->ormRepository->find($id->value());

        if (null === $ormEntity) {
            return null;
        }

        return $this->toDomainEntity($ormEntity);
    }

    /** @return array{items: User[], total: int} */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $ormEntities = $this->ormRepository->findAllPaginated($criteria, $page, $perPage);
        $total = $this->ormRepository->countAll($criteria);

        return [
            'items' => array_map(
                fn (UserOrmEntity $ormEntity): User => $this->toDomainEntity($ormEntity),
                $ormEntities,
            ),
            'total' => $total,
        ];
    }

    public function existsByEmail(Email $email): bool
    {
        return $this->ormRepository->existsByEmail($email->value());
    }

    public function countActiveUsersByGroupId(GroupId $groupId): int
    {
        return $this->ormRepository->countActiveByGroupId($groupId->value());
    }

    public function countActiveUsersByRoleId(string $roleId): int
    {
        return $this->ormRepository->countActiveByRoleId($roleId);
    }

    public function hasTicketsForUser(UserId $userId): bool
    {
        return $this->ormRepository->hasTicketsForUser($userId->value());
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(User $user): UserOrmEntity
    {
        $ormEntity = new UserOrmEntity();
        $ormEntity->setId($user->getId()->value());
        $ormEntity->setName($user->getName());
        $ormEntity->setEmail($user->getEmail()->value());
        $ormEntity->setPasswordHash($user->getPasswordHash()->value());
        $ormEntity->setSystemRole($user->getSystemRole()->value);
        $ormEntity->setGroupId($user->getGroupId()?->value());
        $ormEntity->setRoleId($user->getRoleId());
        $ormEntity->setIsActive($user->isActive());
        $ormEntity->setDeletedAt($user->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(UserOrmEntity $ormEntity, User $user): void
    {
        $ormEntity->setName($user->getName());
        $ormEntity->setEmail($user->getEmail()->value());
        $ormEntity->setPasswordHash($user->getPasswordHash()->value());
        $ormEntity->setSystemRole($user->getSystemRole()->value);
        $ormEntity->setGroupId($user->getGroupId()?->value());
        $ormEntity->setRoleId($user->getRoleId());
        $ormEntity->setIsActive($user->isActive());
        $ormEntity->setDeletedAt($user->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(UserOrmEntity $ormEntity): User
    {
        return User::restore(
            new UserId($ormEntity->getId()),
            $ormEntity->getName(),
            new Email($ormEntity->getEmail()),
            new HashedPassword($ormEntity->getPasswordHash()),
            SystemRole::from($ormEntity->getSystemRole()),
            null !== $ormEntity->getGroupId() ? new GroupId($ormEntity->getGroupId()) : null,
            $ormEntity->getRoleId(),
            $ormEntity->isActive(),
            $ormEntity->getDeletedAt(),
        );
    }
}
