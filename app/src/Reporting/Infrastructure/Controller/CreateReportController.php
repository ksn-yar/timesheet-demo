<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Controller;

use App\Reporting\Application\UseCase\CreateReportUseCase;
use App\Reporting\Infrastructure\Dto\CreateReportRequestDto;
use App\Reporting\Infrastructure\Transformer\CreateReportInputTransformer;
use DomainException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания отчёта. */
#[OA\Tag(name: 'Reporting')]
#[OA\Post(
    summary: 'Создать отчёт',
    description: 'Создаёт новый отчёт по заданным параметрам и фильтрам.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: CreateReportRequestDto::class),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Отчёт успешно создан.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Ошибка бизнес-логики.',
)]
#[Route('/api/reporting/reports', name: 'reporting_create_report', methods: ['POST'])]
final class CreateReportController extends AbstractController
{
    public function __construct(
        private readonly CreateReportUseCase $useCase,
        private readonly CreateReportInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateReportRequestDto::class)]
        CreateReportRequestDto $dto,
    ): JsonResponse {
        try {
            $inputDto = $this->transformer->transform($dto);
            $this->useCase->execute($inputDto);
        } catch (DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['id' => $inputDto->reportId], Response::HTTP_CREATED);
    }
}
