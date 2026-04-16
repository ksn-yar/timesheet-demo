<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Port;

use App\Timesheet\Application\Port\ExternalDataFetcherInterface;
use DateTimeImmutable;

/** Заглушка для получения внешних данных. Возвращает пустой массив для первой итерации. */
final class NullExternalDataFetcher implements ExternalDataFetcherInterface
{
    /**
     * @param array<string, mixed> $mappingRules
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetch(string $sourceSystem, array $mappingRules, DateTimeImmutable $dateFrom, DateTimeImmutable $dateTo): array
    {
        return [];
    }
}
