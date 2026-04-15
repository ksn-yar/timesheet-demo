<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\CreateWorkInputDto;
use App\WorkCatalog\Domain\Entity\Work;
use App\WorkCatalog\Domain\Exception\DuplicateWorkNameException;
use App\WorkCatalog\Domain\Repository\WorkRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case создания нового вида работ. */
final class CreateWorkUseCase
{
    public function __construct(
        private readonly WorkRepositoryInterface $workRepository,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(CreateWorkInputDto $input): void
    {
        $existing = $this->workRepository->findByName($input->name);

        if (null !== $existing && !$existing->isDeleted()) {
            throw new DuplicateWorkNameException($input->name);
        }

        $work = Work::create($input->name, $input->description);

        $this->workRepository->save($work);

        foreach ($work->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
