<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Repository;

use App\Persistence\Entity\Ticket as TicketOrmEntity;
use App\Persistence\Repository\TicketRepository as TicketOrmRepository;
use App\Timesheet\Domain\Entity\Ticket;
use App\Timesheet\Domain\Repository\TicketRepositoryInterface;
use App\Timesheet\Domain\ValueObject\TicketId;
use DateTimeImmutable;

/**
 * Doctrine-реализация хранилища Ticket.
 * Использует composition с Doctrine Repository из Persistence-слоя.
 */
final class TicketRepository implements TicketRepositoryInterface
{
    public function __construct(
        private readonly TicketOrmRepository $ormRepository,
    ) {}

    public function save(Ticket $ticket): void
    {
        $existing = $this->ormRepository->find($ticket->getId()->value());

        if (null !== $existing) {
            $this->updateOrmEntity($existing, $ticket);
            $this->ormRepository->save($existing, flush: true);

            return;
        }

        $this->ormRepository->save($this->toOrmEntity($ticket), flush: true);
    }

    public function findById(TicketId $id): ?Ticket
    {
        $orm = $this->ormRepository->find($id->value());

        return null !== $orm ? $this->toDomainEntity($orm) : null;
    }

    /** @return Ticket[] */
    public function findAll(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        return array_map(
            fn (TicketOrmEntity $orm): Ticket => $this->toDomainEntity($orm),
            $this->ormRepository->findAllPaginated($criteria, $page, $perPage),
        );
    }

    /**
     * Поиск тикетов с именами связанных сущностей.
     *
     * @return array<array{ticket: Ticket, employeeName: string, taskName: string, workName: string}>
     */
    public function findAllWithNames(array $criteria = [], int $page = 1, int $perPage = 20): array
    {
        $rows = $this->ormRepository->findAllWithNamesPaginated($criteria, $page, $perPage);

        return array_map(fn (array $row): array => [
            'ticket' => $this->toDomainEntity($row[0]),
            'employeeName' => $row['employeeName'] ?? '',
            'taskName' => $row['taskName'] ?? '',
            'workName' => $row['workName'] ?? '',
        ], $rows);
    }

    public function countAll(array $criteria = []): int
    {
        return $this->ormRepository->countAll($criteria);
    }

    public function existsByImportSourceAndExternalId(string $importSource, string $externalId): bool
    {
        return $this->ormRepository->existsByImportSourceAndExternalId($importSource, $externalId);
    }

    /** Преобразует доменную сущность в новую Doctrine Entity. */
    private function toOrmEntity(Ticket $ticket): TicketOrmEntity
    {
        $orm = new TicketOrmEntity();
        $orm->setId($ticket->getId()->value());
        $orm->setEmployeeId($ticket->getEmployeeId());
        $orm->setTaskId($ticket->getTaskId());
        $orm->setWorkId($ticket->getWorkId());
        $orm->setDate($ticket->getDate());
        $orm->setHours($ticket->getHours());
        $orm->setComment($ticket->getComment());
        $orm->setRateSnapshot($ticket->getRateSnapshot());
        $orm->setType($ticket->getType()->value);
        $orm->setImportSource($ticket->getImportSource());
        $orm->setExternalId($ticket->getExternalId());
        $orm->setIsEditable($ticket->isEditable());
        $orm->setCreatedAt(new DateTimeImmutable());

        return $orm;
    }

    /** Обновляет существующую Doctrine Entity из доменной сущности. */
    private function updateOrmEntity(TicketOrmEntity $orm, Ticket $ticket): void
    {
        $orm->setDate($ticket->getDate());
        $orm->setHours($ticket->getHours());
        $orm->setComment($ticket->getComment());
        $orm->setWorkId($ticket->getWorkId());
        $orm->setIsEditable($ticket->isEditable());
        $orm->setUpdatedAt(new DateTimeImmutable());
    }

    /** Восстанавливает доменную сущность из Doctrine Entity. */
    private function toDomainEntity(TicketOrmEntity $orm): Ticket
    {
        return Ticket::restore(
            $orm->getId(),
            $orm->getEmployeeId(),
            $orm->getTaskId(),
            $orm->getWorkId(),
            $orm->getDate(),
            $orm->getHours(),
            $orm->getComment(),
            $orm->getRateSnapshot(),
            $orm->getType(),
            $orm->getImportSource(),
            $orm->getExternalId(),
            $orm->isEditable(),
        );
    }
}
