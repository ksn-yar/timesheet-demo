<?php

declare(strict_types=1);

namespace App\Persistence\Entity;

use App\Persistence\Repository\TicketRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/** Doctrine-сущность тикета учёта времени. Чистый ORM-класс без бизнес-логики. */
#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\Table(name: 'tickets')]
#[ORM\Index(columns: ['employee_id'], name: 'idx_tickets_employee_id')]
#[ORM\Index(columns: ['task_id'], name: 'idx_tickets_task_id')]
#[ORM\Index(columns: ['work_id'], name: 'idx_tickets_work_id')]
#[ORM\Index(columns: ['date'], name: 'idx_tickets_date')]
#[ORM\UniqueConstraint(name: 'uq_tickets_import_source_external_id', columns: ['import_source', 'external_id'])]
class Ticket
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    /** Связь с сотрудником — владелец ассоциации (хранит FK в своей таблице). */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'employee_id', referencedColumnName: 'id', nullable: false)]
    private User $employee;

    /** Связь с задачей — владелец ассоциации (хранит FK в своей таблице). */
    #[ORM\ManyToOne(targetEntity: Task::class)]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', nullable: false)]
    private Task $task;

    /** Связь с видом работ — владелец ассоциации (хранит FK в своей таблице). */
    #[ORM\ManyToOne(targetEntity: Work::class)]
    #[ORM\JoinColumn(name: 'work_id', referencedColumnName: 'id', nullable: false)]
    private Work $work;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $date;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2)]
    private string $hours;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(name: 'rate_snapshot', type: 'decimal', precision: 12, scale: 2)]
    private string $rateSnapshot;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type;

    #[ORM\Column(name: 'import_source', type: 'string', length: 255, nullable: true)]
    private ?string $importSource = null;

    #[ORM\Column(name: 'external_id', type: 'string', length: 255, nullable: true)]
    private ?string $externalId = null;

    #[ORM\Column(name: 'is_editable', type: 'boolean', options: ['default' => true])]
    private bool $isEditable = true;

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

    public function getEmployee(): User
    {
        return $this->employee;
    }

    public function setEmployee(User $employee): void
    {
        $this->employee = $employee;
    }

    /** Возвращает UUID сотрудника для обратной совместимости с существующим кодом. */
    public function getEmployeeId(): string
    {
        return $this->employee->getId();
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function setTask(Task $task): void
    {
        $this->task = $task;
    }

    /** Возвращает UUID задачи для обратной совместимости с существующим кодом. */
    public function getTaskId(): string
    {
        return $this->task->getId();
    }

    public function getWork(): Work
    {
        return $this->work;
    }

    public function setWork(Work $work): void
    {
        $this->work = $work;
    }

    /** Возвращает UUID вида работ для обратной совместимости с существующим кодом. */
    public function getWorkId(): string
    {
        return $this->work->getId();
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getHours(): string
    {
        return $this->hours;
    }

    public function setHours(string $hours): void
    {
        $this->hours = $hours;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): void
    {
        $this->comment = $comment;
    }

    public function getRateSnapshot(): string
    {
        return $this->rateSnapshot;
    }

    public function setRateSnapshot(string $rateSnapshot): void
    {
        $this->rateSnapshot = $rateSnapshot;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getImportSource(): ?string
    {
        return $this->importSource;
    }

    public function setImportSource(?string $importSource): void
    {
        $this->importSource = $importSource;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId): void
    {
        $this->externalId = $externalId;
    }

    public function isEditable(): bool
    {
        return $this->isEditable;
    }

    public function setIsEditable(bool $isEditable): void
    {
        $this->isEditable = $isEditable;
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
