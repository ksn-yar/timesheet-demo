<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Repository;

use App\Persistence\Entity\Client as ClientOrmEntity;
use App\Persistence\Repository\ClientRepository as ClientOrmRepository;
use App\Persistence\Repository\ProjectRepository as ProjectOrmRepository;
use App\ProjectManagement\Domain\Entity\Client;
use App\ProjectManagement\Domain\Repository\ClientRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ClientId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища Client.
 * Использует composition с Doctrine Repository из Persistence-домена.
 */
final class ClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private readonly ClientOrmRepository $ormRepository,
        private readonly ProjectOrmRepository $projectOrmRepository,
    ) {}

    public function save(Client $client): void
    {
        $existingOrmEntity = $this->ormRepository->find($client->getId()->value());

        if (null !== $existingOrmEntity) {
            $this->updateOrmEntity($existingOrmEntity, $client);
            $this->ormRepository->save($existingOrmEntity, flush: true);

            return;
        }

        $ormEntity = $this->toOrmEntity($client);
        $this->ormRepository->save($ormEntity, flush: true);
    }

    public function findById(ClientId $id): ?Client
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
     * @return Client[]
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $ormEntities = $this->ormRepository->findAllPaginated($criteria, $page, $perPage);

        return array_map(
            fn (ClientOrmEntity $ormEntity): Client => $this->toDomainEntity($ormEntity),
            $ormEntities,
        );
    }

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countAll($criteria);
    }

    public function countActiveProjectsByClientId(ClientId $clientId): int
    {
        return $this->projectOrmRepository->countActiveByClientId($clientId->value());
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(Client $client): ClientOrmEntity
    {
        $ormEntity = new ClientOrmEntity();
        $ormEntity->setId($client->getId()->value());
        $ormEntity->setName($client->getName());
        $ormEntity->setDescription($client->getDescription());
        $ormEntity->setDeletedAt($client->getDeletedAt());
        $ormEntity->setCreatedAt(new DateTimeImmutable());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());

        return $ormEntity;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(ClientOrmEntity $ormEntity, Client $client): void
    {
        $ormEntity->setName($client->getName());
        $ormEntity->setDescription($client->getDescription());
        $ormEntity->setDeletedAt($client->getDeletedAt());
        $ormEntity->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(ClientOrmEntity $ormEntity): Client
    {
        return Client::restore(
            $ormEntity->getId(),
            $ormEntity->getName(),
            $ormEntity->getDescription(),
            $ormEntity->getDeletedAt(),
        );
    }
}
