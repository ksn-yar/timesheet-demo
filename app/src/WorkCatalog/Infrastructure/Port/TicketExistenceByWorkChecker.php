<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Port;

use App\WorkCatalog\Application\Port\TicketExistenceByWorkCheckerInterface;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use Doctrine\DBAL\Connection;

/** Проверяет наличие тикетов, привязанных к виду работ, через DBAL-запрос. */
final class TicketExistenceByWorkChecker implements TicketExistenceByWorkCheckerInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function hasTicketsForWork(WorkId $workId): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tickets WHERE work_id = :workId',
            ['workId' => $workId->value()],
        );

        return (int) $count > 0;
    }
}
