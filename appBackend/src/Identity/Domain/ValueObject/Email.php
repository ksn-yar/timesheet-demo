<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObject;

use InvalidArgumentException;

/** Адрес электронной почты. Валидирует формат и нормализует в нижний регистр. */
final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));

        if (!filter_var($normalized, \FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Некорректный email: {$value}");
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
