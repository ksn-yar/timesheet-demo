<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

/** Данные сформированного отчёта. Неизменяемый контейнер строк результата. */
final readonly class ReportData
{
    /** @param array<int, array<string, mixed>> $rows */
    public function __construct(
        private array $rows,
    ) {}

    /** @param array<int, array<string, mixed>> $data */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /** @return array<int, array<string, mixed>> */
    public function rows(): array
    {
        return $this->rows;
    }

    /** @return array<int, array<string, mixed>> */
    public function toArray(): array
    {
        return $this->rows;
    }
}
