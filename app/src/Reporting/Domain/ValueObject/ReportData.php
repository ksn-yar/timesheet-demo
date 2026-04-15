<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

/** Данные сформированного отчёта. Неизменяемый контейнер строк результата. */
final readonly class ReportData
{
    public function __construct(
        private array $rows,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public function rows(): array
    {
        return $this->rows;
    }

    public function toArray(): array
    {
        return $this->rows;
    }
}
