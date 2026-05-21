<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

/** Набор фильтров для формирования отчёта. Все фильтры опциональны и задаются списком идентификаторов. */
final readonly class ReportFilters
{
    /**
     * @param null|string[] $employeeIds
     * @param null|string[] $groupIds
     * @param null|string[] $projectIds
     * @param null|string[] $crIds
     * @param null|string[] $taskIds
     * @param null|string[] $workIds
     */
    public function __construct(
        private ?array $employeeIds = null,
        private ?array $groupIds = null,
        private ?array $projectIds = null,
        private ?array $crIds = null,
        private ?array $taskIds = null,
        private ?array $workIds = null,
    ) {}

    /** @param array<string, mixed> $data */
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

    /** @return null|string[] */
    public function employeeIds(): ?array
    {
        return $this->employeeIds;
    }

    /** @return null|string[] */
    public function groupIds(): ?array
    {
        return $this->groupIds;
    }

    /** @return null|string[] */
    public function projectIds(): ?array
    {
        return $this->projectIds;
    }

    /** @return null|string[] */
    public function crIds(): ?array
    {
        return $this->crIds;
    }

    /** @return null|string[] */
    public function taskIds(): ?array
    {
        return $this->taskIds;
    }

    /** @return null|string[] */
    public function workIds(): ?array
    {
        return $this->workIds;
    }

    /** @return array<string, null|string[]> */
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
