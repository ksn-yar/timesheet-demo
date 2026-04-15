<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\ListChangeRequestsUseCase;
use App\ProjectManagement\Infrastructure\Dto\ChangeRequestListResponseDto;
use App\ProjectManagement\Infrastructure\Dto\ListChangeRequestsRequestDto;
use App\ProjectManagement\Infrastructure\Presenter\HttpListChangeRequestsPresenter;
use App\ProjectManagement\Infrastructure\Transformer\ListChangeRequestsInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка запросов на изменение. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Get(
    summary: 'Получить список запросов на изменение',
    description: 'Возвращает список запросов на изменение с пагинацией и фильтрацией.',
)]
#[OA\Parameter(name: 'projectId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по проекту.')]
#[OA\Parameter(name: 'name', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Фильтр по названию.')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список запросов на изменение.',
    content: new OA\JsonContent(ref: ChangeRequestListResponseDto::class),
)]
#[Route('/api/project-management/change-requests', name: 'project_management_list_change_requests', methods: ['GET'])]
final class ListChangeRequestsController extends AbstractController
{
    public function __construct(
        private readonly ListChangeRequestsUseCase $useCase,
        private readonly ListChangeRequestsInputTransformer $transformer,
        private readonly HttpListChangeRequestsPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListChangeRequestsRequestDto::class)]
        ListChangeRequestsRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
