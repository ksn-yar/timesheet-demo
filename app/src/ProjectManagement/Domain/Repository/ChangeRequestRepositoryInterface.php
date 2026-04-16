<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Repository;

use App\ProjectManagement\Domain\Entity\ChangeRequest;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;

/** Контракт хранилища агрегатов ChangeRequest. Определяет доменные операции доступа к данным. */
interface ChangeRequestRepositoryInterface
{
    public function save(ChangeRequest $changeRequest): void;

    public function findById(ChangeRequestId $id): ?ChangeRequest;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return ChangeRequest[]
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array;

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int;

    public function countActiveTasksByChangeRequestId(ChangeRequestId $changeRequestId): int;
}
