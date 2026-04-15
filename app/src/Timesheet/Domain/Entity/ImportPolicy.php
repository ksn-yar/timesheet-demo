<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Entity;

use App\Timesheet\Domain\Event\ImportPolicyCreated;
use App\Timesheet\Domain\Event\ImportPolicyUpdated;
use App\Timesheet\Domain\Trait\RecordsDomainEventsTrait;
use App\Timesheet\Domain\ValueObject\ImportPolicyId;
use InvalidArgumentException;

/** Сущность политики импорта. Определяет правила загрузки тикетов из внешней системы. */
final class ImportPolicy
{
    use RecordsDomainEventsTrait;

    private ImportPolicyId $id;
    private string $name;
    private string $sourceSystem;
    private array $mappingRules;
    private bool $allowEdit;
    private bool $isActive;

    private function __construct() {}

    public static function create(
        ImportPolicyId $id,
        string $name,
        string $sourceSystem,
        array $mappingRules,
        bool $allowEdit,
    ): self {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название политики импорта не может быть пустым.');
        }

        if ('' === trim($sourceSystem)) {
            throw new InvalidArgumentException('Источник системы не может быть пустым.');
        }

        $policy = new self();
        $policy->id = $id;
        $policy->name = $name;
        $policy->sourceSystem = $sourceSystem;
        $policy->mappingRules = $mappingRules;
        $policy->allowEdit = $allowEdit;
        $policy->isActive = false;

        $policy->recordEvent(new ImportPolicyCreated(
            $policy->id,
            $policy->name,
            $policy->sourceSystem,
        ));

        return $policy;
    }

    public static function restore(
        string $id,
        string $name,
        string $sourceSystem,
        array $mappingRules,
        bool $allowEdit,
        bool $isActive,
    ): self {
        $policy = new self();
        $policy->id = new ImportPolicyId($id);
        $policy->name = $name;
        $policy->sourceSystem = $sourceSystem;
        $policy->mappingRules = $mappingRules;
        $policy->allowEdit = $allowEdit;
        $policy->isActive = $isActive;

        return $policy;
    }

    public function update(
        ?array $mappingRules,
        ?bool $allowEdit,
        ?bool $isActive,
    ): void {
        if (null !== $mappingRules) {
            $this->mappingRules = $mappingRules;
        }

        if (null !== $allowEdit) {
            $this->allowEdit = $allowEdit;
        }

        if (null !== $isActive) {
            $this->isActive = $isActive;
        }

        $this->recordEvent(new ImportPolicyUpdated($this->id));
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function getId(): ImportPolicyId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSourceSystem(): string
    {
        return $this->sourceSystem;
    }

    public function getMappingRules(): array
    {
        return $this->mappingRules;
    }

    public function isAllowEdit(): bool
    {
        return $this->allowEdit;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }
}
