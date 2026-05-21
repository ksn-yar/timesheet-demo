<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\ListRatesUseCase;
use App\WorkCatalog\Infrastructure\Dto\ListRatesRequestDto;
use App\WorkCatalog\Infrastructure\Dto\RateListResponseDto;
use App\WorkCatalog\Infrastructure\Presenter\HttpListRatesPresenter;
use App\WorkCatalog\Infrastructure\Transformer\ListRatesInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер получения списка ставок. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Get(
    summary: 'Получить список ставок',
    description: 'Возвращает список ставок с пагинацией и фильтрацией.',
)]
#[OA\Parameter(name: 'roleId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по роли.')]
#[OA\Parameter(name: 'workId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по виду работ.')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список ставок.',
    content: new OA\JsonContent(ref: RateListResponseDto::class),
)]
#[Route('/api/work-catalog/rates', name: 'work_catalog_list_rates', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListRatesController extends AbstractController
{
    public function __construct(
        private readonly ListRatesUseCase $useCase,
        private readonly ListRatesInputTransformer $transformer,
        private readonly HttpListRatesPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListRatesRequestDto::class)]
        ListRatesRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
