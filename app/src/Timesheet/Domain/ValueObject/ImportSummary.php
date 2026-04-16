<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\ValueObject;

/** Итоговая сводка импорта: счётчики и детализированный лог записей. */
final readonly class ImportSummary
{
    /**
     * @param ImportLogEntry[] $logEntries
     */
    public function __construct(
        private int $imported,
        private int $duplicates,
        private int $errors,
        private array $logEntries,
    ) {}

    public function imported(): int
    {
        return $this->imported;
    }

    public function duplicates(): int
    {
        return $this->duplicates;
    }

    public function errors(): int
    {
        return $this->errors;
    }

    /** @return ImportLogEntry[] */
    public function logEntries(): array
    {
        return $this->logEntries;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'imported' => $this->imported,
            'duplicates' => $this->duplicates,
            'errors' => $this->errors,
            'logEntries' => array_map(
                static fn (ImportLogEntry $entry): array => [
                    'externalId' => $entry->externalId(),
                    'status' => $entry->status()->value,
                    'errorReason' => $entry->errorReason(),
                ],
                $this->logEntries,
            ),
        ];
    }
}
