<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Entity;

use App\Timesheet\Domain\Enum\TicketType;
use App\Timesheet\Domain\Event\TicketsAdded;
use App\Timesheet\Domain\Event\TicketUpdated;
use App\Timesheet\Domain\Exception\InvalidTicketHoursException;
use App\Timesheet\Domain\Exception\TicketNotEditableException;
use App\Timesheet\Domain\Trait\RecordsDomainEventsTrait;
use App\Timesheet\Domain\ValueObject\TicketId;
use DateTimeImmutable;
use InvalidArgumentException;

/** Сущность тикета учёта рабочего времени. Представляет запись о трудозатратах сотрудника. */
final class Ticket
{
    use RecordsDomainEventsTrait;

    private TicketId $id;
    private string $employeeId;
    private string $taskId;
    private string $workId;
    private DateTimeImmutable $date;
    private string $hours;
    private ?string $comment;
    private string $rateSnapshot;
    private TicketType $type;
    private ?string $importSource;
    private ?string $externalId;
    private bool $isEditable;

    private function __construct() {}

    public static function createManual(
        TicketId $id,
        string $employeeId,
        string $taskId,
        string $workId,
        DateTimeImmutable $date,
        string $hours,
        ?string $comment,
        string $rateSnapshot,
    ): self {
        if ((float) $hours <= 0.0) {
            throw new InvalidTicketHoursException($hours);
        }

        $ticket = new self();
        $ticket->id = $id;
        $ticket->employeeId = $employeeId;
        $ticket->taskId = $taskId;
        $ticket->workId = $workId;
        $ticket->date = $date;
        $ticket->hours = $hours;
        $ticket->comment = $comment;
        $ticket->rateSnapshot = $rateSnapshot;
        $ticket->type = TicketType::Manual;
        $ticket->importSource = null;
        $ticket->externalId = null;
        $ticket->isEditable = true;

        $ticket->recordEvent(new TicketsAdded(
            $ticket->id,
            $ticket->employeeId,
            $ticket->taskId,
            $ticket->workId,
            $date->format('Y-m-d'),
            $ticket->hours,
            $ticket->rateSnapshot,
        ));

        return $ticket;
    }

    public static function createImported(
        TicketId $id,
        string $employeeId,
        string $taskId,
        string $workId,
        DateTimeImmutable $date,
        string $hours,
        ?string $comment,
        string $rateSnapshot,
        string $importSource,
        string $externalId,
        bool $allowEdit,
    ): self {
        if ((float) $hours <= 0.0) {
            throw new InvalidTicketHoursException($hours);
        }

        if ('' === trim($importSource)) {
            throw new InvalidArgumentException('Источник импорта не может быть пустым.');
        }

        if ('' === trim($externalId)) {
            throw new InvalidArgumentException('Внешний идентификатор не может быть пустым.');
        }

        $ticket = new self();
        $ticket->id = $id;
        $ticket->employeeId = $employeeId;
        $ticket->taskId = $taskId;
        $ticket->workId = $workId;
        $ticket->date = $date;
        $ticket->hours = $hours;
        $ticket->comment = $comment;
        $ticket->rateSnapshot = $rateSnapshot;
        $ticket->type = TicketType::Imported;
        $ticket->importSource = $importSource;
        $ticket->externalId = $externalId;
        $ticket->isEditable = $allowEdit;

        return $ticket;
    }

    public static function restore(
        string $id,
        string $employeeId,
        string $taskId,
        string $workId,
        DateTimeImmutable $date,
        string $hours,
        ?string $comment,
        string $rateSnapshot,
        TicketType $type,
        ?string $importSource,
        ?string $externalId,
        bool $isEditable,
    ): self {
        $ticket = new self();
        $ticket->id = new TicketId($id);
        $ticket->employeeId = $employeeId;
        $ticket->taskId = $taskId;
        $ticket->workId = $workId;
        $ticket->date = $date;
        $ticket->hours = $hours;
        $ticket->comment = $comment;
        $ticket->rateSnapshot = $rateSnapshot;
        $ticket->type = $type;
        $ticket->importSource = $importSource;
        $ticket->externalId = $externalId;
        $ticket->isEditable = $isEditable;

        return $ticket;
    }

    public function update(
        ?DateTimeImmutable $date,
        ?string $hours,
        ?string $workId,
        mixed $comment = '__NOT_SET__',
    ): void {
        if (!$this->isEditable) {
            throw new TicketNotEditableException($this->id->value());
        }

        if (null !== $hours) {
            if ((float) $hours <= 0.0) {
                throw new InvalidTicketHoursException($hours);
            }
            $this->hours = $hours;
        }

        if (null !== $date) {
            $this->date = $date;
        }

        if (null !== $workId) {
            $this->workId = $workId;
        }

        if ('__NOT_SET__' !== $comment) {
            $this->comment = $comment;
        }

        $this->recordEvent(new TicketUpdated($this->id));
    }

    public function getId(): TicketId
    {
        return $this->id;
    }

    public function getEmployeeId(): string
    {
        return $this->employeeId;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function getWorkId(): string
    {
        return $this->workId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getHours(): string
    {
        return $this->hours;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getRateSnapshot(): string
    {
        return $this->rateSnapshot;
    }

    public function getType(): TicketType
    {
        return $this->type;
    }

    public function getImportSource(): ?string
    {
        return $this->importSource;
    }

    public function getExternalId(): ?string
    {
        return $this->externalId;
    }

    public function isEditable(): bool
    {
        return $this->isEditable;
    }
}
