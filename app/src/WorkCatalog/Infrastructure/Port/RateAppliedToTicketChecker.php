<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Port;

use App\WorkCatalog\Application\Port\RateAppliedToTicketCheckerInterface;
use App\WorkCatalog\Domain\ValueObject\RateId;
use Doctrine\DBAL\Connection;

/** Проверяет, применена ли ставка хотя бы к одному тикету, через DBAL-запрос. */
final class RateAppliedToTicketChecker implements RateAppliedToTicketCheckerInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function isRateAppliedToTicket(RateId $rateId): bool
    {
        $count = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM tickets WHERE rate_id = :rateId',
            ['rateId' => $rateId->value()],
        );

        return (int) $count > 0;
    }
}
