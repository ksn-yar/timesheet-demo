<?php

declare(strict_types=1);

namespace App\WorkCatalog\Application\UseCase;

use App\WorkCatalog\Application\Dto\ListWorksInputDto;
use App\WorkCatalog\Application\Dto\ListWorksOutputDto;
use App\WorkCatalog\Application\Dto\WorkItemDto;
use App\WorkCatalog\Application\Port\ListWorksOutputPortInterface;
use App\WorkCatalog\Domain\Entity\Work;
use App\WorkCatalog\Domain\Repository\WorkRepositoryInterface;

/** Use Case получения списка видов работ с пагинацией. */
final class ListWorksUseCase
{
    public function __construct(
        private readonly WorkRepositoryInterface $workRepository,
        private readonly ListWorksOutputPortInterface $presenter,
    ) {}

    public function execute(ListWorksInputDto $input): void
    {
        $criteria = [];
        $offset = ($input->page - 1) * $input->perPage;

        $works = $this->workRepository->findAll($criteria, $input->perPage, $offset);
        $total = $this->workRepository->countAll($criteria);

        $items = array_map(
            static fn (Work $work): WorkItemDto => new WorkItemDto(
                id: $work->getId()->value(),
                name: $work->getName(),
                description: $work->getDescription(),
            ),
            $works,
        );

        $this->presenter->present(new ListWorksOutputDto(
            items: $items,
            total: $total,
            page: $input->page,
            perPage: $input->perPage,
        ));
    }
}
