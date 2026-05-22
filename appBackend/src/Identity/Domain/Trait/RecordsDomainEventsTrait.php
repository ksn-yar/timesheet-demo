<?php

declare(strict_types=1);

namespace App\Identity\Domain\Trait;

/** Накапливает доменные события для последующей публикации через Use Case. */
trait RecordsDomainEventsTrait
{
    /** @var object[] */
    private array $domainEvents = [];

    /** @return object[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    protected function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
