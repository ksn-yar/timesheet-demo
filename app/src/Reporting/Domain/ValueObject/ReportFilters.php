<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

/** Набор фильтров для формирования отчёта. Все фильтры опциональны и задаются списком идентификаторов. */
final readonly class ReportFilters
{
    public function __construct(
        private ?array $employeeIds = null,
        private ?array $groupIds = null,
        private ?array $projectIds = null,
        private ?array $crIds = null,
        private ?array $taskIds = null,
        private ?array $workIds = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            employeeIds: $data['employeeIds'] ?? null,
            groupIds: $data['groupIds'] ?? null,
            projectIds: $data['projectIds'] ?? null,
            crIds: $data['crIds'] ?? null,
            taskIds: $data['taskIds'] ?? null,
            workIds: $data['workIds'] ?? null,
        );
    }

    public function employeeIds(): ?array
    {
        return $this->employeeIds;
    }

    public function groupIds(): ?array
    {
        return $this->groupIds;
    }

    public function projectIds(): ?array
    {
        return $this->projectIds;
    }

    public function crIds(): ?array
    {
        return $this->crIds;
    }

    public function taskIds(): ?array
    {
        return $this->taskIds;
    }

    public function workIds(): ?array
    {
        return $this->workIds;
    }

    public function toArray(): array
    {
        return [
            'employeeIds' => $this->employeeIds,
            'groupIds' => $this->groupIds,
            'projectIds' => $this->projectIds,
            'crIds' => $this->crIds,
            'taskIds' => $this->taskIds,
            'workIds' => $this->workIds,
        ];
    }
}
