<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Entity;

use App\ProjectManagement\Domain\Enum\ProjectStatus;
use App\ProjectManagement\Domain\Event\ProjectCreated;
use App\ProjectManagement\Domain\Event\ProjectDeleted;
use App\ProjectManagement\Domain\Event\ProjectUpdated;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Trait\RecordsDomainEventsTrait;
use App\ProjectManagement\Domain\ValueObject\ClientId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use DateTimeImmutable;
use InvalidArgumentException;

/** Сущность проекта. Принадлежит клиенту, содержит задачи и запросы на изменение. */
final class Project
{
    use RecordsDomainEventsTrait;

    private ProjectId $id;
    private ClientId $clientId;
    private string $name;
    private ProjectStatus $status;
    private ?string $description;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function create(
        string $clientId,
        string $name,
        ProjectStatus $status,
        ?string $description,
    ): self {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название проекта не может быть пустым.');
        }

        $project = new self();
        $project->id = ProjectId::generate();
        $project->clientId = new ClientId($clientId);
        $project->name = $name;
        $project->status = $status;
        $project->description = $description;
        $project->deletedAt = null;

        $project->recordEvent(new ProjectCreated(
            $project->id,
            $project->clientId,
            $project->name,
            $project->status,
        ));

        return $project;
    }

    public static function restore(
        string $id,
        string $clientId,
        string $name,
        ProjectStatus $status,
        ?string $description,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $project = new self();
        $project->id = new ProjectId($id);
        $project->clientId = new ClientId($clientId);
        $project->name = $name;
        $project->status = $status;
        $project->description = $description;
        $project->deletedAt = $deletedAt;

        return $project;
    }

    public function update(
        ?string $name,
        ?ProjectStatus $status,
        mixed $description = '__NOT_SET__',
    ): void {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        if (null !== $name) {
            if ('' === trim($name)) {
                throw new InvalidArgumentException('Название проекта не может быть пустым.');
            }
            $this->name = $name;
        }

        if (null !== $status) {
            $this->status = $status;
        }

        if ('__NOT_SET__' !== $description) {
            $this->description = $description;
        }

        $this->recordEvent(new ProjectUpdated($this->id));
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();

        $this->recordEvent(new ProjectDeleted($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function isActive(): bool
    {
        return ProjectStatus::Active === $this->status && !$this->isDeleted();
    }

    public function getId(): ProjectId
    {
        return $this->id;
    }

    public function getClientId(): ClientId
    {
        return $this->clientId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
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
