<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Controller;

use App\Reporting\Application\UseCase\ListReportsUseCase;
use App\Reporting\Infrastructure\Dto\ListReportsRequestDto;
use App\Reporting\Infrastructure\Dto\ReportListResponseDto;
use App\Reporting\Infrastructure\Presenter\HttpListReportsPresenter;
use App\Reporting\Infrastructure\Transformer\ListReportsInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка отчётов с пагинацией и фильтрацией. */
#[OA\Tag(name: 'Reporting')]
#[OA\Get(
    summary: 'Получить список отчётов',
    description: 'Возвращает список отчётов с пагинацией и опциональной фильтрацией.',
)]
#[OA\Parameter(name: 'createdBy', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по автору.')]
#[OA\Parameter(name: 'periodFrom', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), description: 'Фильтр по началу периода (Y-m-d).')]
#[OA\Parameter(name: 'periodTo', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), description: 'Фильтр по окончанию периода (Y-m-d).')]
#[OA\Parameter(name: 'name', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Фильтр по названию (поиск подстроки).')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список отчётов.',
    content: new OA\JsonContent(ref: ReportListResponseDto::class),
)]
#[Route('/api/reporting/reports', name: 'reporting_list_reports', methods: ['GET'])]
final class ListReportsController extends AbstractController
{
    public function __construct(
        private readonly ListReportsUseCase $useCase,
        private readonly ListReportsInputTransformer $transformer,
        private readonly HttpListReportsPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListReportsRequestDto::class)]
        ListReportsRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
