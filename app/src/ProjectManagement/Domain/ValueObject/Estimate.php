<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\ValueObject;

use InvalidArgumentException;

/** Оценка трудозатрат задачи в часах. Значение должно быть положительным. */
final readonly class Estimate
{
    public function __construct(
        private float $value,
    ) {
        if ($this->value <= 0) {
            throw new InvalidArgumentException("Оценка должна быть положительным числом, получено: {$this->value}");
        }
    }

    public function value(): float
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
