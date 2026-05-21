<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\ValueObject;

use InvalidArgumentException;

/** Денежная сумма с валютой. Гарантирует положительный amount и валидный код валюты. */
final readonly class Money
{
    public function __construct(
        private string $amount,
        private string $currency,
    ) {
        if (!is_numeric($this->amount) || bccomp($this->amount, '0', 10) <= 0) {
            throw new InvalidArgumentException("Сумма должна быть положительным числом, получено: {$this->amount}");
        }

        $trimmedCurrency = trim($this->currency);
        if ('' === $trimmedCurrency || 3 !== mb_strlen($trimmedCurrency)) {
            throw new InvalidArgumentException("Код валюты должен быть строкой из 3 символов, получено: «{$this->currency}»");
        }
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function equals(self $other): bool
    {
        return 0 === bccomp($this->amount, $other->amount, 10)
            && $this->currency === $other->currency;
    }
}
