<?php

declare(strict_types=1);

namespace App\Persistence\Entity;

use App\Persistence\Repository\RateRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/** Doctrine-сущность ставки. Чистый ORM-класс без бизнес-логики. */
#[ORM\Entity(repositoryClass: RateRepository::class)]
#[ORM\Table(name: 'rates')]
#[ORM\Index(columns: ['deleted_at'], name: 'idx_rates_deleted_at')]
#[ORM\Index(columns: ['role_id'], name: 'idx_rates_role_id')]
#[ORM\Index(columns: ['work_id'], name: 'idx_rates_work_id')]
class Rate
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $id;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $amount;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(name: 'effective_from', type: 'date_immutable')]
    private DateTimeImmutable $effectiveFrom;

    #[ORM\Column(name: 'role_id', type: 'guid', nullable: true)]
    private ?string $roleId = null;

    #[ORM\Column(name: 'work_id', type: 'guid', nullable: true)]
    private ?string $workId = null;

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

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getEffectiveFrom(): DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function setEffectiveFrom(DateTimeImmutable $effectiveFrom): void
    {
        $this->effectiveFrom = $effectiveFrom;
    }

    public function getRoleId(): ?string
    {
        return $this->roleId;
    }

    public function setRoleId(?string $roleId): void
    {
        $this->roleId = $roleId;
    }

    public function getWorkId(): ?string
    {
        return $this->workId;
    }

    public function setWorkId(?string $workId): void
    {
        $this->workId = $workId;
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
