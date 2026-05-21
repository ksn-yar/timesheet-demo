<?php

declare(strict_types=1);

namespace App\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/** Идентификатор группы. Валидирует формат UUID. */
final readonly class GroupId
{
    public function __construct(
        private string $value,
    ) {
        if (!Uuid::isValid($this->value)) {
            throw new InvalidArgumentException("Некорректный GroupId: {$this->value}");
        }
    }

    public static function generate(): self
    {
        return new self(Uuid::v4()->toRfc4122());
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
