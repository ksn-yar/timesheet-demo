<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\DeleteRateInputDto;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\RateNotFoundException;
use App\WorkCatalog\Domain\Repository\RateRepositoryInterface;
use App\WorkCatalog\Domain\ValueObject\RateId;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/** Use Case удаления ставки (soft delete). */
final readonly class DeleteRateUseCase
{
    public function __construct(
        private RateRepositoryInterface $rateRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function execute(DeleteRateInputDto $input): void
    {
        $rateId = new RateId($input->id);
        $rate = $this->rateRepository->findById($rateId);

        if (null === $rate) {
            throw new RateNotFoundException($input->id);
        }

        if ($rate->isDeleted()) {
            throw new EntityDeletedException();
        }

        $rate->softDelete();

        $this->rateRepository->save($rate);

        foreach ($rate->pullDomainEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
