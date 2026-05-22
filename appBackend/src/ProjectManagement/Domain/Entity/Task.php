<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Entity;

use App\ProjectManagement\Domain\Event\TaskCreated;
use App\ProjectManagement\Domain\Event\TaskDeleted;
use App\ProjectManagement\Domain\Event\TaskUpdated;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Trait\RecordsDomainEventsTrait;
use App\ProjectManagement\Domain\ValueObject\ChangeRequestId;
use App\ProjectManagement\Domain\ValueObject\Estimate;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use App\ProjectManagement\Domain\ValueObject\TaskId;
use DateTimeImmutable;
use InvalidArgumentException;

/** Сущность задачи. Принадлежит проекту или запросу на изменение, имеет оценку трудозатрат. */
final class Task
{
    use RecordsDomainEventsTrait;

    private TaskId $id;
    private ?ProjectId $projectId;
    private ?ChangeRequestId $crId;
    private string $name;
    private ?string $description;
    private ?Estimate $estimate;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function createForProject(
        string $projectId,
        string $name,
        ?string $description,
        ?float $estimate,
    ): self {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название задачи не может быть пустым.');
        }

        $task = new self();
        $task->id = TaskId::generate();
        $task->projectId = new ProjectId($projectId);
        $task->crId = null;
        $task->name = $name;
        $task->description = $description;
        $task->estimate = null !== $estimate ? new Estimate($estimate) : null;
        $task->deletedAt = null;

        $task->recordEvent(new TaskCreated($task->id, $task->projectId, null, $task->name));

        return $task;
    }

    public static function createForChangeRequest(
        string $crId,
        string $name,
        ?string $description,
        ?float $estimate,
    ): self {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название задачи не может быть пустым.');
        }

        $task = new self();
        $task->id = TaskId::generate();
        $task->projectId = null;
        $task->crId = new ChangeRequestId($crId);
        $task->name = $name;
        $task->description = $description;
        $task->estimate = null !== $estimate ? new Estimate($estimate) : null;
        $task->deletedAt = null;

        $task->recordEvent(new TaskCreated($task->id, null, $task->crId, $task->name));

        return $task;
    }

    public static function restore(
        string $id,
        ?string $projectId,
        ?string $crId,
        string $name,
        ?string $description,
        ?float $estimate,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $task = new self();
        $task->id = new TaskId($id);
        $task->projectId = null !== $projectId ? new ProjectId($projectId) : null;
        $task->crId = null !== $crId ? new ChangeRequestId($crId) : null;
        $task->name = $name;
        $task->description = $description;
        $task->estimate = null !== $estimate ? new Estimate($estimate) : null;
        $task->deletedAt = $deletedAt;

        return $task;
    }

    public function update(
        ?string $name,
        mixed $description = '__NOT_SET__',
        mixed $estimate = '__NOT_SET__',
    ): void {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        if (null !== $name) {
            if ('' === trim($name)) {
                throw new InvalidArgumentException('Название задачи не может быть пустым.');
            }
            $this->name = $name;
        }

        if ('__NOT_SET__' !== $description) {
            $this->description = $description;
        }

        if ('__NOT_SET__' !== $estimate) {
            $this->estimate = null !== $estimate ? new Estimate($estimate) : null;
        }

        $this->recordEvent(new TaskUpdated($this->id));
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();

        $this->recordEvent(new TaskDeleted($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getId(): TaskId
    {
        return $this->id;
    }

    public function getProjectId(): ?ProjectId
    {
        return $this->projectId;
    }

    public function getCrId(): ?ChangeRequestId
    {
        return $this->crId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getEstimate(): ?Estimate
    {
        return $this->estimate;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
