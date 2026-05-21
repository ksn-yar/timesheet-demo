<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Entity;

use App\ProjectManagement\Domain\Event\ClientCreated;
use App\ProjectManagement\Domain\Event\ClientDeleted;
use App\ProjectManagement\Domain\Event\ClientUpdated;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Trait\RecordsDomainEventsTrait;
use App\ProjectManagement\Domain\ValueObject\ClientId;
use DateTimeImmutable;
use InvalidArgumentException;

/** Сущность клиента. Представляет заказчика, которому принадлежат проекты. */
final class Client
{
    use RecordsDomainEventsTrait;

    private ClientId $id;
    private string $name;
    private ?string $description;
    private ?DateTimeImmutable $deletedAt;

    private function __construct() {}

    public static function create(string $name, ?string $description): self
    {
        if ('' === trim($name)) {
            throw new InvalidArgumentException('Название клиента не может быть пустым.');
        }

        $client = new self();
        $client->id = ClientId::generate();
        $client->name = $name;
        $client->description = $description;
        $client->deletedAt = null;

        $client->recordEvent(new ClientCreated($client->id, $client->name));

        return $client;
    }

    public static function restore(
        string $id,
        string $name,
        ?string $description,
        ?DateTimeImmutable $deletedAt,
    ): self {
        $client = new self();
        $client->id = new ClientId($id);
        $client->name = $name;
        $client->description = $description;
        $client->deletedAt = $deletedAt;

        return $client;
    }

    public function update(?string $name, mixed $description = '__NOT_SET__'): void
    {
        if ($this->isDeleted()) {
            throw new EntityDeletedException();
        }

        if (null !== $name) {
            if ('' === trim($name)) {
                throw new InvalidArgumentException('Название клиента не может быть пустым.');
            }
            $this->name = $name;
        }

        if ('__NOT_SET__' !== $description) {
            $this->description = $description;
        }

        $this->recordEvent(new ClientUpdated($this->id));
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable();

        $this->recordEvent(new ClientDeleted($this->id));
    }

    public function isDeleted(): bool
    {
        return null !== $this->deletedAt;
    }

    public function getId(): ClientId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
