<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Repository;

use App\ProjectManagement\Domain\Entity\Client;
use App\ProjectManagement\Domain\ValueObject\ClientId;

/** Контракт хранилища агрегатов Client. Определяет доменные операции доступа к данным. */
interface ClientRepositoryInterface
{
    public function save(Client $client): void;

    public function findById(ClientId $id): ?Client;

    /** @return Client[] */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array;

    public function countAll(array $criteria = []): int;

    public function countActiveProjectsByClientId(ClientId $clientId): int;
}
