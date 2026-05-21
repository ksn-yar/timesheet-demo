<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\ListTasksUseCase;
use App\ProjectManagement\Infrastructure\Dto\ListTasksRequestDto;
use App\ProjectManagement\Infrastructure\Dto\TaskListResponseDto;
use App\ProjectManagement\Infrastructure\Presenter\HttpListTasksPresenter;
use App\ProjectManagement\Infrastructure\Transformer\ListTasksInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка задач. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Get(
    summary: 'Получить список задач',
    description: 'Возвращает список задач с пагинацией и фильтрацией.',
)]
#[OA\Parameter(name: 'projectId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по проекту.')]
#[OA\Parameter(name: 'crId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по запросу на изменение.')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список задач.',
    content: new OA\JsonContent(ref: TaskListResponseDto::class),
)]
#[Route('/api/project-management/tasks', name: 'project_management_list_tasks', methods: ['GET'])]
final class ListTasksController extends AbstractController
{
    public function __construct(
        private readonly ListTasksUseCase $useCase,
        private readonly ListTasksInputTransformer $transformer,
        private readonly HttpListTasksPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListTasksRequestDto::class)]
        ListTasksRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
