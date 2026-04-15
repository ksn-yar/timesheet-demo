<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Entity;

use App\WorkCatalog\Domain\Event\WorkCreated;
use App\WorkCatalog\Domain\Event\WorkDeleted;
use App\WorkCatalog\Domain\Trait\RecordsDomainEventsTrait;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use DateTimeImmutable;
use InvalidArgumentException;

/** Сущность вида работ. Представляет категорию работ, к которой могут привязываться ставки. */
final class Work
{
    use RecordsDomainEventsTrait;

    private WorkId $id;
    private string $name;
    private ?string $description;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function create(
        string $name,
        ?string $description,
    ): self {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название вида работ не может быть пустым.');
        }

        $work = new self();
        $work->id = WorkId::generate();
        $work->name = $name;
        $work->description = $description;
        $work->deletedAt = null;

        $work->recordEvent(new WorkCreated($work->id, $work->name));

        return $work;
    }

    public static function restore(
        WorkId $id,
        string $name,
        ?string $description,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $work = new self();
        $work->id = $id;
        $work->name = $name;
        $work->description = $description;
        $work->deletedAt = $deletedAt;

        return $work;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();

        $this->recordEvent(new WorkDeleted($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getId(): WorkId
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
