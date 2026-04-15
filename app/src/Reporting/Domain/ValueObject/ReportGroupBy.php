<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

use App\Reporting\Domain\Enum\GroupByDimension;
use App\Reporting\Domain\Exception\EmptyGroupByException;

/** Конфигурация группировки отчёта. Гарантирует наличие хотя бы одного измерения. */
final readonly class ReportGroupBy
{
    /** @param GroupByDimension[] $dimensions */
    public function __construct(
        private array $dimensions,
    ) {
        if (empty($this->dimensions)) {
            throw new EmptyGroupByException();
        }
    }

    public static function fromArray(array $data): self
    {
        $dimensions = array_map(
            static fn (string $value): GroupByDimension => GroupByDimension::from($value),
            $data,
        );

        return new self($dimensions);
    }

    /** @return GroupByDimension[] */
    public function dimensions(): array
    {
        return $this->dimensions;
    }

    public function toArray(): array
    {
        return array_map(
            static fn (GroupByDimension $dimension): string => $dimension->value,
            $this->dimensions,
        );
    }
}
