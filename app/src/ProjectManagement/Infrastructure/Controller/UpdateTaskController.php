<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\UpdateTaskUseCase;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Exception\TaskNotFoundException;
use App\ProjectManagement\Infrastructure\Dto\UpdateTaskRequestDto;
use App\ProjectManagement\Infrastructure\Transformer\UpdateTaskInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления задачи. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Put(
    summary: 'Обновить задачу',
    description: 'Обновляет данные существующей задачи.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор задачи.')]
#[OA\RequestBody(required: true, content: new OA\JsonContent(ref: new OA\Schema(type: UpdateTaskRequestDto::class)))]
#[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Задача успешно обновлена.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Задача не найдена.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/project-management/tasks/{id}', name: 'project_management_update_task', methods: ['PUT'])]
final class UpdateTaskController extends AbstractController
{
    public function __construct(
        private readonly UpdateTaskUseCase $useCase,
        private readonly UpdateTaskInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateTaskRequestDto::class)]
        UpdateTaskRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (TaskNotFoundException) {
            return new JsonResponse(['error' => 'Задача не найдена.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
