<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\EventListener;

use App\Timesheet\Domain\Event\ImportPolicyCreated;
use App\Timesheet\Domain\Event\ImportPolicyUpdated;
use App\Timesheet\Domain\Event\TicketsAdded;
use App\Timesheet\Domain\Event\TicketsImported;
use App\Timesheet\Domain\Event\TicketUpdated;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/** Слушатель доменных событий Timesheet для ведения аудит-лога. */
final class AuditLogEventListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function onTicketsAdded(TicketsAdded $event): void
    {
        $this->log('create', 'Ticket', $event->ticketId->value());
    }

    public function onTicketUpdated(TicketUpdated $event): void
    {
        $this->log('update', 'Ticket', $event->ticketId->value());
    }

    public function onTicketsImported(TicketsImported $event): void
    {
        $userId = $this->resolveUserId();

        $this->logger->info('audit_log', [
            'userId' => $userId,
            'action' => 'import',
            'entityType' => 'Ticket',
            'entityId' => $event->importPolicyId->value(),
            'imported' => $event->imported,
            'duplicates' => $event->duplicates,
            'errors' => $event->errors,
            'timestamp' => new DateTimeImmutable()->format(DateTimeInterface::RFC3339),
        ]);
    }

    public function onImportPolicyCreated(ImportPolicyCreated $event): void
    {
        $this->log('create', 'ImportPolicy', $event->importPolicyId->value());
    }

    public function onImportPolicyUpdated(ImportPolicyUpdated $event): void
    {
        $this->log('update', 'ImportPolicy', $event->importPolicyId->value());
    }

    private function log(string $action, string $entityType, string $entityId): void
    {
        $userId = $this->resolveUserId();

        $this->logger->info('audit_log', [
            'userId' => $userId,
            'action' => $action,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'timestamp' => new DateTimeImmutable()->format(DateTimeInterface::RFC3339),
        ]);
    }

    private function resolveUserId(): ?string
    {
        $token = $this->tokenStorage->getToken();

        if (null !== $token && null !== $token->getUser()) {
            $user = $token->getUser();

            return method_exists($user, 'getId')
                ? (string) $user->getId()
                : $user->getUserIdentifier();
        }

        return null;
    }
}
