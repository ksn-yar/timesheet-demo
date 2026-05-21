<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entity;

use App\Identity\Domain\Event\GroupCreated;
use App\Identity\Domain\Event\GroupDeleted;
use App\Identity\Domain\Event\GroupUpdated;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Trait\RecordsDomainEventsTrait;
use App\Identity\Domain\ValueObject\GroupId;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Сущность группы пользователей. Объединяет пользователей по организационному признаку. */
final class Group
{
    use RecordsDomainEventsTrait;

    private GroupId $id;
    private string $name;
    private ?string $description;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function create(GroupId $id, string $name, ?string $description): self
    {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название группы не может быть пустым.');
        }

        $group = new self();
        $group->id = $id;
        $group->name = $name;
        $group->description = $description;
        $group->deletedAt = null;

        $group->recordEvent(new GroupCreated($group->id, $group->name));

        return $group;
    }

    public static function restore(
        GroupId $id,
        string $name,
        ?string $description,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $group = new self();
        $group->id = $id;
        $group->name = $name;
        $group->description = $description;
        $group->deletedAt = $deletedAt;

        return $group;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $this->recordEvent(new GroupDeleted($this->id));
    }

    public function update(string $name, ?string $description): void
    {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название группы не может быть пустым.');
        }

        $this->name = $name;
        $this->description = $description;

        $this->recordEvent(new GroupUpdated($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getId(): GroupId
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
