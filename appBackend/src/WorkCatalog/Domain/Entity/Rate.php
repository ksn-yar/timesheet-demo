<?php

declare(strict_types=1);

namespace App\WorkCatalog\Domain\Entity;

use App\WorkCatalog\Domain\Event\RateCreated;
use App\WorkCatalog\Domain\Event\RateDeleted;
use App\WorkCatalog\Domain\Event\RateUpdated;
use App\WorkCatalog\Domain\Exception\RateEffectiveFromImmutableException;
use App\WorkCatalog\Domain\Trait\RecordsDomainEventsTrait;
use App\WorkCatalog\Domain\ValueObject\Money;
use App\WorkCatalog\Domain\ValueObject\RateId;
use App\WorkCatalog\Domain\ValueObject\RoleId;
use App\WorkCatalog\Domain\ValueObject\WorkId;
use DateTimeImmutable;

/** Сущность ставки. Определяет стоимость работы за единицу времени с привязкой к роли и/или виду работ. */
final class Rate
{
    use RecordsDomainEventsTrait;

    private RateId $id;
    private Money $money;
    private DateTimeImmutable $effectiveFrom;
    private ?RoleId $roleId;
    private ?WorkId $workId;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function create(
        Money $money,
        DateTimeImmutable $effectiveFrom,
        ?RoleId $roleId,
        ?WorkId $workId,
    ): self {
        $rate = new self();
        $rate->id = RateId::generate();
        $rate->money = $money;
        $rate->effectiveFrom = $effectiveFrom;
        $rate->roleId = $roleId;
        $rate->workId = $workId;
        $rate->deletedAt = null;

        $rate->recordEvent(new RateCreated(
            $rate->id,
            $money->amount(),
            $money->currency(),
            $effectiveFrom->format('Y-m-d'),
            $roleId?->value(),
            $workId?->value(),
        ));

        return $rate;
    }

    public static function restore(
        RateId $id,
        Money $money,
        DateTimeImmutable $effectiveFrom,
        ?RoleId $roleId,
        ?WorkId $workId,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $rate = new self();
        $rate->id = $id;
        $rate->money = $money;
        $rate->effectiveFrom = $effectiveFrom;
        $rate->roleId = $roleId;
        $rate->workId = $workId;
        $rate->deletedAt = $deletedAt;

        return $rate;
    }

    public function update(
        Money $money,
        DateTimeImmutable $effectiveFrom,
        ?RoleId $roleId,
        ?WorkId $workId,
        bool $isAppliedToTicket,
    ): void {
        if ($isAppliedToTicket && $effectiveFrom != $this->effectiveFrom) {
            throw new RateEffectiveFromImmutableException($this->id->value());
        }

        $this->money = $money;
        $this->effectiveFrom = $effectiveFrom;
        $this->roleId = $roleId;
        $this->workId = $workId;

        $this->recordEvent(new RateUpdated($this->id));
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();

        $this->recordEvent(new RateDeleted($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getId(): RateId
    {
        return $this->id;
    }

    public function getMoney(): Money
    {
        return $this->money;
    }

    public function getEffectiveFrom(): DateTimeImmutable
    {
        return $this->effectiveFrom;
    }

    public function getRoleId(): ?RoleId
    {
        return $this->roleId;
    }

    public function getWorkId(): ?WorkId
    {
        return $this->workId;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
