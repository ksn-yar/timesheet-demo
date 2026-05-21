<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Port;

use App\Persistence\Repository\WorkRepository;
use App\Timesheet\Application\Port\WorkExistenceCheckerInterface;

/** Проверяет существование вида работ через Doctrine ORM. */
final readonly class DoctrineWorkExistenceChecker implements WorkExistenceCheckerInterface
{
    public function __construct(
        private WorkRepository $workRepository,
    ) {}

    public function workExists(string $workId): bool
    {
        $work = $this->workRepository->find($workId);

        return null !== $work && null === $work->getDeletedAt();
    }
}
