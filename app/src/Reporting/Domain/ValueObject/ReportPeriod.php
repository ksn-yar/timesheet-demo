<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

use App\Reporting\Domain\Exception\InvalidReportPeriodException;
use DateTimeImmutable;

/** Период отчёта. Гарантирует инвариант ИН-23: дата начала не превышает дату окончания. */
final readonly class ReportPeriod
{
    public function __construct(
        private DateTimeImmutable $from,
        private DateTimeImmutable $to,
    ) {
        if ($this->from > $this->to) {
            throw new InvalidReportPeriodException();
        }
    }

    public function from(): DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): DateTimeImmutable
    {
        return $this->to;
    }
}
