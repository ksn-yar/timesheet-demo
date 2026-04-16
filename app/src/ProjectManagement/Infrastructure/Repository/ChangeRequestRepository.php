<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\Persistence\Entity\ChangeRequest as ChangeRequestOrmEntity;
use App\Persistence\Repository\ChangeRequestRepository as ChangeRequestOrmRepository;
use App\ProjectManagement\Domain\Entity\ChangeRequest;
use App\ProjectManagement\Domain\Repository\ChangeRequestRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища ChangeRequest.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class ChangeRequestRepository implements ChangeRequestRepositoryInterface
{
    public function __construct(
        private readonly ChangeRequestOrmRepository $ormRepository,
    ) {}

    public function save(ChangeRequest $changeRequest): void
    {
        $existingOrmEntity = $this->ormRepository->find($changeRequest->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $changeRequest);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($changeRequest);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(ChangeRequestId $id): ?ChangeRequest
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
     * @return ChangeRequest[]
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $ormEntities = $this->ormRepository->findAllPaginated($criteria, $page, $perPage);

        return array_map(
            fn (ChangeRequestOrmEntity $ormEntity): ChangeRequest => $this->toDomainEntity($ormEntity),
            $ormEntities,
        );
    }

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countAll($criteria);
    }

    public function countActiveTasksByChangeRequestId(ChangeRequestId $changeRequestId): int
    {
        return $this->ormRepository->countActiveTasksByChangeRequestId($changeRequestId->value());
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(ChangeRequest $changeRequest): ChangeRequestOrmEntity
    {
        $ormEntity = new ChangeRequestOrmEntity();
        $ormEntity->setId($changeRequest->getId()->value());
        $ormEntity->setProjectId($changeRequest->getProjectId()->value());
        $ormEntity->setName($changeRequest->getName());
        $ormEntity->setDescription($changeRequest->getDescription());
        $ormEntity->setDeletedAt($changeRequest->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(ChangeRequestOrmEntity $ormEntity, ChangeRequest $changeRequest): void
    {
        $ormEntity->setName($changeRequest->getName());
        $ormEntity->setDescription($changeRequest->getDescription());
        $ormEntity->setDeletedAt($changeRequest->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(ChangeRequestOrmEntity $ormEntity): ChangeRequest
    {
        return ChangeRequest::restore(
            $ormEntity->getId(),
            $ormEntity->getProjectId(),
            $ormEntity->getName(),
            $ormEntity->getDescription(),
            $ormEntity->getDeletedAt(),
        );
    }
}
