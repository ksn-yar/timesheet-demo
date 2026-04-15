<?php

declare(strict_types=1);

namespace App\Persistence\Entity;

use App\Persistence\Repository\ImportPolicyRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/** Doctrine-сущность политики импорта. Чистый ORM-класс без бизнес-логики. */
#[ORM\Entity(repositoryClass: ImportPolicyRepository::class)]
#[ORM\Table(name: 'import_policies')]
#[ORM\Index(columns: ['source_system'], name: 'idx_import_policies_source_system')]
class ImportPolicy
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'source_system', type: 'string', length: 255)]
    private string $sourceSystem;

    #[ORM\Column(name: 'mapping_rules', type: 'json')]
    private array $mappingRules = [];

    #[ORM\Column(name: 'allow_edit', type: 'boolean', options: ['default' => false])]
    private bool $allowEdit = false;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => false])]
    private bool $isActive = false;

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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getSourceSystem(): string
    {
        return $this->sourceSystem;
    }

    public function setSourceSystem(string $sourceSystem): void
    {
        $this->sourceSystem = $sourceSystem;
    }

    public function getMappingRules(): array
    {
        return $this->mappingRules;
    }

    public function setMappingRules(array $mappingRules): void
    {
        $this->mappingRules = $mappingRules;
    }

    public function isAllowEdit(): bool
    {
        return $this->allowEdit;
    }

    public function setAllowEdit(bool $allowEdit): void
    {
        $this->allowEdit = $allowEdit;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
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
