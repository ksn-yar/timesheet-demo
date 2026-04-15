<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\ValueObject;

use App\Timesheet\Domain\Enum\ImportLogEntryStatus;
use InvalidArgumentException;

/** Запись лога импорта: результат обработки одной внешней записи. */
final readonly class ImportLogEntry
{
    public function __construct(
        private string $externalId,
        private ImportLogEntryStatus $status,
        private ?string $errorReason = null,
    ) {
        if (ImportLogEntryStatus::Error === $this->status && (null === $this->errorReason || '' === trim($this->errorReason))) {
            throw new InvalidArgumentException('Причина ошибки обязательна для статуса Error.');
        }
    }

    public function externalId(): string
    {
        return $this->externalId;
    }

    public function status(): ImportLogEntryStatus
    {
        return $this->status;
    }

    public function errorReason(): ?string
    {
        return $this->errorReason;
    }
}
