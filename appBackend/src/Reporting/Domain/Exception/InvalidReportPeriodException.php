<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Exception;

use DomainException;

/** Нарушение инварианта ИН-23: дата начала периода превышает дату окончания. */
final class InvalidReportPeriodException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Дата начала периода не может быть больше даты окончания.');
    }
}
