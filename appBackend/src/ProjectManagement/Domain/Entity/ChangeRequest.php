<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Entity;

use App\ProjectManagement\Domain\Event\ChangeRequestCreated;
use App\ProjectManagement\Domain\Event\ChangeRequestDeleted;
use App\ProjectManagement\Domain\Event\ChangeRequestUpdated;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Trait\RecordsDomainEventsTrait;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use DateTimeImmutable;
use InvalidArgumentException;

/** Сущность запроса на изменение. Принадлежит проекту, группирует связанные задачи. */
final class ChangeRequest
{
    use RecordsDomainEventsTrait;

    private ChangeRequestId $id;
    private ProjectId $projectId;
    private string $name;
    private ?string $description;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function create(string $projectId, string $name, ?string $description): self
    {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название запроса на изменение не может быть пустым.');
        }

        $cr = new self();
        $cr->id = ChangeRequestId::generate();
        $cr->projectId = new ProjectId($projectId);
        $cr->name = $name;
        $cr->description = $description;
        $cr->deletedAt = null;

        $cr->recordEvent(new ChangeRequestCreated($cr->id, $cr->projectId, $cr->name));

        return $cr;
    }

    public static function restore(
        string $id,
        string $projectId,
        string $name,
        ?string $description,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $cr = new self();
        $cr->id = new ChangeRequestId($id);
        $cr->projectId = new ProjectId($projectId);
        $cr->name = $name;
        $cr->description = $description;
        $cr->deletedAt = $deletedAt;

        return $cr;
    }

    public function update(?string $name, mixed $description = '__NOT_SET__'): void
    {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        if (null !== $name) {
            if ('' === trim($name)) {
                throw new InvalidArgumentException('Название запроса на изменение не может быть пустым.');
            }
            $this->name = $name;
        }

        if ('__NOT_SET__' !== $description) {
            $this->description = $description;
        }

        $this->recordEvent(new ChangeRequestUpdated($this->id));
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();

        $this->recordEvent(new ChangeRequestDeleted($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getId(): ChangeRequestId
    {
        return $this->id;
    }

    public function getProjectId(): ProjectId
    {
        return $this->projectId;
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
