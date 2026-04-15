<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\EventListener;

use App\ProjectManagement\Domain\Event\ChangeRequestCreated;
use App\ProjectManagement\Domain\Event\ChangeRequestDeleted;
use App\ProjectManagement\Domain\Event\ChangeRequestUpdated;
use App\ProjectManagement\Domain\Event\ClientCreated;
use App\ProjectManagement\Domain\Event\ClientDeleted;
use App\ProjectManagement\Domain\Event\ClientUpdated;
use App\ProjectManagement\Domain\Event\ProjectCreated;
use App\ProjectManagement\Domain\Event\ProjectDeleted;
use App\ProjectManagement\Domain\Event\ProjectUpdated;
use App\ProjectManagement\Domain\Event\TaskCreated;
use App\ProjectManagement\Domain\Event\TaskDeleted;
use App\ProjectManagement\Domain\Event\TaskUpdated;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/** Слушатель доменных событий Project Management для ведения аудит-лога. */
final class AuditLogEventListener
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function onClientCreated(ClientCreated $event): void
    {
        $this->log('create', 'Client', $event->clientId->value());
    }

    public function onClientUpdated(ClientUpdated $event): void
    {
        $this->log('update', 'Client', $event->clientId->value());
    }

    public function onClientDeleted(ClientDeleted $event): void
    {
        $this->log('delete', 'Client', $event->clientId->value());
    }

    public function onProjectCreated(ProjectCreated $event): void
    {
        $this->log('create', 'Project', $event->projectId->value());
    }

    public function onProjectUpdated(ProjectUpdated $event): void
    {
        $this->log('update', 'Project', $event->projectId->value());
    }

    public function onProjectDeleted(ProjectDeleted $event): void
    {
        $this->log('delete', 'Project', $event->projectId->value());
    }

    public function onChangeRequestCreated(ChangeRequestCreated $event): void
    {
        $this->log('create', 'ChangeRequest', $event->changeRequestId->value());
    }

    public function onChangeRequestUpdated(ChangeRequestUpdated $event): void
    {
        $this->log('update', 'ChangeRequest', $event->changeRequestId->value());
    }

    public function onChangeRequestDeleted(ChangeRequestDeleted $event): void
    {
        $this->log('delete', 'ChangeRequest', $event->changeRequestId->value());
    }

    public function onTaskCreated(TaskCreated $event): void
    {
        $this->log('create', 'Task', $event->taskId->value());
    }

    public function onTaskUpdated(TaskUpdated $event): void
    {
        $this->log('update', 'Task', $event->taskId->value());
    }

    public function onTaskDeleted(TaskDeleted $event): void
    {
        $this->log('delete', 'Task', $event->taskId->value());
    }

    private function log(string $action, string $entityType, string $entityId): void
    {
        $userId = null;
        $token = $this->tokenStorage->getToken();

        if (null !== $token && null !== $token->getUser()) {
            $user = $token->getUser();
            $userId = method_exists($user, 'getId')
                ? (string) $user->getId()
                : $user->getUserIdentifier();
        }

        $this->logger->info('audit_log', [
            'userId' => $userId,
            'action' => $action,
            'entityType' => $entityType,
            'entityId' => $entityId,
            'timestamp' => new DateTimeImmutable()->format(DateTimeInterface::RFC3339),
        ]);
    }
}
