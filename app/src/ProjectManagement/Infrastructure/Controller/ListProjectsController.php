<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\ListProjectsUseCase;
use App\ProjectManagement\Infrastructure\Dto\ListProjectsRequestDto;
use App\ProjectManagement\Infrastructure\Dto\ProjectListResponseDto;
use App\ProjectManagement\Infrastructure\Presenter\HttpListProjectsPresenter;
use App\ProjectManagement\Infrastructure\Transformer\ListProjectsInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка проектов. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Get(
    summary: 'Получить список проектов',
    description: 'Возвращает список проектов с пагинацией и фильтрацией.',
)]
#[OA\Parameter(name: 'clientId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по клиенту.')]
#[OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'closed']), description: 'Фильтр по статусу.')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список проектов.',
    content: new OA\JsonContent(ref: ProjectListResponseDto::class),
)]
#[Route('/api/project-management/projects', name: 'project_management_list_projects', methods: ['GET'])]
final class ListProjectsController extends AbstractController
{
    public function __construct(
        private readonly ListProjectsUseCase $useCase,
        private readonly ListProjectsInputTransformer $transformer,
        private readonly HttpListProjectsPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListProjectsRequestDto::class)]
        ListProjectsRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
