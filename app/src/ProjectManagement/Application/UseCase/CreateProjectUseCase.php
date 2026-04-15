<?php

declare(strict_types=1);

namespace App\ProjectManagement\Application\UseCase;

use App\ProjectManagement\Application\Dto\CreateProjectInputDto;
use App\ProjectManagement\Domain\Entity\Project;
use App\ProjectManagement\Domain\Enum\ProjectStatus;
use App\ProjectManagement\Domain\Event\ProjectCreated;
use App\ProjectManagement\Domain\Exception\ClientNotFoundException;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Repository\ClientRepositoryInterface;
use App\ProjectManagement\Domain\Repository\ProjectRepositoryInterface;
use App\ProjectManagement\Domain\ValueObject\ClientId;
use App\ProjectManagement\Domain\ValueObject\ProjectId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания нового проекта. */
final class CreateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
        private readonly ClientRepositoryInterface $clientRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateProjectInputDto $input): void
    {
        $clientId = new ClientId($input->clientId);
        $client = $this->clientRepository->findById($clientId);

        if (null === $client) {
            throw new ClientNotFoundException($input->clientId);
        }

        if ($client->isDeleted()) {
            throw new EntityDeletedException();
        }

        $status = ProjectStatus::from($input->status);

        $project = Project::create(
            $input->clientId,
            $input->name,
            $status,
            $input->description,
        );

        $this->projectRepository->save($project);

        $this->eventDispatcher->dispatch(new ProjectCreated(
            new ProjectId($project->getId()->value()),
            $clientId,
            $project->getName(),
            $status,
        ));
    }
}
