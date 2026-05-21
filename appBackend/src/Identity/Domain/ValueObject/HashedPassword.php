<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObject;

/** Хэшированный пароль. Хранит уже готовый хэш без логики хэширования. */
final readonly class HashedPassword
{
    public function __construct(
        private string $hash,
    ) {}

    public function value(): string
    {
        return $this->hash;
    }
}
