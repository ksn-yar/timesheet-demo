<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Exception;

use DomainException;

/** Нарушение инварианта: список измерений группировки не может быть пустым. */
final class EmptyGroupByException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Необходимо указать хотя бы одно измерение группировки.');
    }
}
