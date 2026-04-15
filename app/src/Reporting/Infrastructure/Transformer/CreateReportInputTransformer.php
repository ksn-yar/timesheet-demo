<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Transformer;

use App\Reporting\Application\Dto\CreateReportInputDto;
use App\Reporting\Domain\ValueObject\ReportId;
use App\Reporting\Infrastructure\Dto\CreateReportRequestDto;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/** Трансформирует CreateReportRequestDto в CreateReportInputDto, генерируя UUID и извлекая ID текущего пользователя. */
final class CreateReportInputTransformer
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function transform(CreateReportRequestDto $dto): CreateReportInputDto
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token || null === $token->getUser()) {
            throw new LogicException('Пользователь не аутентифицирован.');
        }

        $user = $token->getUser();
        $createdBy = method_exists($user, 'getId')
            ? (string) $user->getId()
            : $user->getUserIdentifier();

        return new CreateReportInputDto(
            reportId: ReportId::generate()->value(),
            name: $dto->name,
            createdBy: $createdBy,
            periodFrom: $dto->periodFrom,
            periodTo: $dto->periodTo,
            filters: $dto->filters,
            groupBy: $dto->groupBy,
        );
    }
}
