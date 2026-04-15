<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Entity;

use App\WorkCatalog\Domain\Event\RoleCreated;
use App\WorkCatalog\Domain\Event\RoleDeleted;
use App\WorkCatalog\Domain\Trait\RecordsDomainEventsTrait;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use DateTimeImmutable;
use InvalidArgumentException;

/** Сущность роли. Представляет должностную роль, к которой могут привязываться ставки. */
final class Role
{
    use RecordsDomainEventsTrait;

    private RoleId $id;
    private string $name;
    private ?string $description;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function create(
        string $name,
        ?string $description,
    ): self {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название роли не может быть пустым.');
        }

        $role = new self();
        $role->id = RoleId::generate();
        $role->name = $name;
        $role->description = $description;
        $role->deletedAt = null;

        $role->recordEvent(new RoleCreated($role->id, $role->name));

        return $role;
    }

    public static function restore(
        RoleId $id,
        string $name,
        ?string $description,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $role = new self();
        $role->id = $id;
        $role->name = $name;
        $role->description = $description;
        $role->deletedAt = $deletedAt;

        return $role;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();

        $this->recordEvent(new RoleDeleted($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getId(): RoleId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
