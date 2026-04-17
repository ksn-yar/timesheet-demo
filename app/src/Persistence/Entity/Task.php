<?php

declare(strict_types=1);

namespace App\Persistence\Entity;

use App\Persistence\Repository\TaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/** Doctrine-сущность задачи. Чистый ORM-класс без бизнес-логики. */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'tasks')]
#[ORM\Index(columns: ['project_id'], name: 'idx_tasks_project_id')]
#[ORM\Index(columns: ['cr_id'], name: 'idx_tasks_cr_id')]
#[ORM\Index(columns: ['deleted_at'], name: 'idx_tasks_deleted_at')]
class Task
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    /** Связь с проектом — владелец ассоциации (хранит FK в своей таблице). */
    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(name: 'project_id', referencedColumnName: 'id', nullable: true)]
    private ?Project $project = null;

    /** Связь с запросом на изменение — владелец ассоциации (хранит FK в своей таблице). */
    #[ORM\ManyToOne(targetEntity: ChangeRequest::class)]
    #[ORM\JoinColumn(name: 'cr_id', referencedColumnName: 'id', nullable: true)]
    private ?ChangeRequest $changeRequest = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $estimate = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): void
    {
        $this->project = $project;
    }

    /** Возвращает UUID проекта для обратной совместимости с существующим кодом. */
    public function getProjectId(): ?string
    {
        return $this->project?->getId();
    }

    public function getChangeRequest(): ?ChangeRequest
    {
        return $this->changeRequest;
    }

    public function setChangeRequest(?ChangeRequest $changeRequest): void
    {
        $this->changeRequest = $changeRequest;
    }

    /** Возвращает UUID запроса на изменение для обратной совместимости с существующим кодом. */
    public function getCrId(): ?string
    {
        return $this->changeRequest?->getId();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getEstimate(): ?string
    {
        return $this->estimate;
    }

    public function setEstimate(?string $estimate): void
    {
        $this->estimate = $estimate;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
