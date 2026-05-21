<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\DeleteTaskUseCase;
use App\ProjectManagement\Domain\Exception\TaskHasLinkedTicketsException;
use App\ProjectManagement\Domain\Exception\TaskNotFoundException;
use App\ProjectManagement\Infrastructure\Transformer\DeleteTaskInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер удаления задачи. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Delete(
    summary: 'Удалить задачу',
    description: 'Выполняет мягкое удаление задачи.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор задачи.')]
#[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Задача успешно удалена.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Задача не найдена.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/project-management/tasks/{id}', name: 'project_management_delete_task', methods: ['DELETE'])]
final class DeleteTaskController extends AbstractController
{
    public function __construct(
        private readonly DeleteTaskUseCase $useCase,
        private readonly DeleteTaskInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (TaskNotFoundException) {
            return new JsonResponse(['error' => 'Задача не найдена.'], Response::HTTP_NOT_FOUND);
        } catch (TaskHasLinkedTicketsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
