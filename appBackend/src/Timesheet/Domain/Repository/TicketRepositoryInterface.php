<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Repository;

use App\Timesheet\Domain\Entity\Ticket;
use App\Timesheet\Domain\ValueObject\TicketId;

/** Контракт хранилища агрегатов Ticket. Определяет доменные операции доступа к данным. */
interface TicketRepositoryInterface
{
    public function save(Ticket $ticket): void;

    public function findById(TicketId $id): ?Ticket;

    /**
     * @param array<string, mixed> $criteria
     *
     * @return Ticket[]
     */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array;

    /** @param array<string, mixed> $criteria */
    public function countAll(array $criteria = []): int;

    public function existsByImportSourceAndExternalId(string $importSource, string $externalId): bool;

    /**
     * Возвращает тикеты с именами связанных сущностей (сотрудника, задачи, вида работ).
     *
     * @param array<string, mixed> $criteria
     *
     * @return array<array{ticket: Ticket, employeeName: string, taskName: string, workName: string}>
     */
    public function findAllWithNames(array $criteria = [], int $page = 1, int $perPage = 20): array;
}
