<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\ListWorksUseCase;
use App\WorkCatalog\Infrastructure\Dto\ListWorksRequestDto;
use App\WorkCatalog\Infrastructure\Dto\WorkListResponseDto;
use App\WorkCatalog\Infrastructure\Presenter\HttpListWorksPresenter;
use App\WorkCatalog\Infrastructure\Transformer\ListWorksInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер получения списка видов работ. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Get(
    summary: 'Получить список видов работ',
    description: 'Возвращает список видов работ с пагинацией.',
)]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список видов работ.',
    content: new OA\JsonContent(ref: WorkListResponseDto::class),
)]
#[Route('/api/work-catalog/works', name: 'work_catalog_list_works', methods: ['GET'])]
#[IsGranted('ROLE_EMPLOYEE')]
final class ListWorksController extends AbstractController
{
    public function __construct(
        private readonly ListWorksUseCase $useCase,
        private readonly ListWorksInputTransformer $transformer,
        private readonly HttpListWorksPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListWorksRequestDto::class)]
        ListWorksRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
