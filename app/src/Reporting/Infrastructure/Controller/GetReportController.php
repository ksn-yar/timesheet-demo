<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Controller;

use App\Reporting\Application\UseCase\GetReportUseCase;
use App\Reporting\Domain\Exception\ReportNotFoundException;
use App\Reporting\Infrastructure\Dto\ReportResponseDto;
use App\Reporting\Infrastructure\Presenter\HttpGetReportPresenter;
use App\Reporting\Infrastructure\Transformer\GetReportInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения отчёта по идентификатору. */
#[OA\Tag(name: 'Reporting')]
#[OA\Get(
    summary: 'Получить отчёт',
    description: 'Возвращает полные данные отчёта, включая строки агрегации.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор отчёта.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Данные отчёта.',
    content: new OA\JsonContent(ref: ReportResponseDto::class),
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Отчёт не найден.',
)]
#[Route('/api/reporting/reports/{id}', name: 'reporting_get_report', methods: ['GET'])]
final class GetReportController extends AbstractController
{
    public function __construct(
        private readonly GetReportUseCase $useCase,
        private readonly GetReportInputTransformer $transformer,
        private readonly HttpGetReportPresenter $presenter,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (ReportNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json($this->presenter->getResponseDto());
    }
}
