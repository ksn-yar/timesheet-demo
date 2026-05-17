<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\DeleteClientInputDto;
use App\ProjectManagement\Domain\Event\ClientDeleted;
use App\ProjectManagement\Domain\Exception\ClientHasActiveProjectsException;
use App\ProjectManagement\Domain\Exception\ClientNotFoundException;
use App\ProjectManagement\Domain\Repository\ClientRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ClientId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case удаления клиента (soft delete). */
final readonly class DeleteClientUseCase
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteClientInputDto $input): void
    {
        $clientId = new ClientId($input->id);
        $client = $this->clientRepository->findById($clientId);

        if (null === $client || $client->isDeleted()) {
            throw new ClientNotFoundException($input->id);
        }

        $activeProjects = $this->clientRepository->countActiveProjectsByClientId($clientId);

        if ($activeProjects > 0) {
            throw new ClientHasActiveProjectsException($input->id);
        }

        $client->softDelete();

        $this->clientRepository->save($client);

        $this->eventDispatcher->dispatch(new ClientDeleted($clientId));
    }
}
