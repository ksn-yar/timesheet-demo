<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\UpdateClientInputDto;
use App\ProjectManagement\Domain\Event\ClientUpdated;
use App\ProjectManagement\Domain\Exception\ClientNotFoundException;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Repository\ClientRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ClientId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case обновления клиента. */
final class UpdateClientUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(UpdateClientInputDto $input): void
    {
        $clientId = new ClientId($input->id);
        $client = $this->clientRepository->findById($clientId);

        if (null === $client) {
            throw new ClientNotFoundException($input->id);
        }

        if ($client->isDeleted()) {
            throw new EntityDeletedException();
        }

        $client->update($input->name, $input->description);

        $this->clientRepository->save($client);

        $this->eventDispatcher->dispatch(new ClientUpdated($clientId));
    }
}
