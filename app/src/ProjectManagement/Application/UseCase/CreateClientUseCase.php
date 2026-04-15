<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\CreateClientInputDto;
use App\ProjectManagement\Domain\Entity\Client;
use App\ProjectManagement\Domain\Event\ClientCreated;
use App\ProjectManagement\Domain\Repository\ClientRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ClientId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания нового клиента. */
final class CreateClientUseCase
{
    public function __construct(
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateClientInputDto $input): void
    {
        $client = Client::create($input->name, $input->description);

        $this->clientRepository->save($client);

        $this->eventDispatcher->dispatch(new ClientCreated(
            new ClientId($client->getId()->value()),
            $client->getName(),
        ));
    }
}
