<?php

declare(strict_types=1);

namespace App\Timesheet\Application\Port;

use DateTimeImmutable;

/** Получает записи из внешней системы для импорта тикетов. */
interface ExternalDataFetcherInterface
{
    /** Получает записи из внешней системы для импорта. Возвращает массив ассоциативных массивов. */
    public function fetch(string $sourceSystem, array $mappingRules, DateTimeImmutable $dateFrom, DateTimeImmutable $dateTo): array;
}
