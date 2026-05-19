<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Controller;

use App\Reporting\Application\UseCase\ListExportedReportsUseCase;
use App\Reporting\Infrastructure\Dto\ExportListResponseDto;
use App\Reporting\Infrastructure\Dto\ListExportedReportsRequestDto;
use App\Reporting\Infrastructure\Presenter\HttpListExportedReportsPresenter;
use App\Reporting\Infrastructure\Transformer\ListExportedReportsInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка выгрузок отчётов с пагинацией и фильтрацией. */
#[OA\Tag(name: 'Reporting')]
#[OA\Get(
    summary: 'Получить список выгрузок',
    description: 'Возвращает список выгрузок отчётов с пагинацией и опциональной фильтрацией.',
)]
#[OA\Parameter(name: 'format', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['csv', 'xlsx', 'pdf']), description: 'Фильтр по формату.')]
#[OA\Parameter(name: 'generatedAtFrom', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time'), description: 'Фильтр по дате генерации (от).')]
#[OA\Parameter(name: 'generatedAtTo', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time'), description: 'Фильтр по дате генерации (до).')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список выгрузок.',
    content: new OA\JsonContent(ref: ExportListResponseDto::class),
)]
#[Route('/reporting/exports', name: 'reporting_list_exported_reports', methods: ['GET'])]
final class ListExportedReportsController extends AbstractController
{
    public function __construct(
        private readonly ListExportedReportsUseCase $useCase,
        private readonly ListExportedReportsInputTransformer $transformer,
        private readonly HttpListExportedReportsPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListExportedReportsRequestDto::class)]
        ListExportedReportsRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
