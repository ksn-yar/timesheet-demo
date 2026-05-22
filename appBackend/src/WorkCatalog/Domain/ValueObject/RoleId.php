<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\ValueObject;

use InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

/** Типизированный UUID-идентификатор роли. */
final readonly class RoleId
{
    public function __construct(
        private string $value,
    ) {
        if (!Uuid::isValid($this->value)) {
            throw new InvalidArgumentException("Некорректный RoleId: {$this->value}");
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
