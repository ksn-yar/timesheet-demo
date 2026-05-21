<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\DeleteProjectUseCase;
use App\ProjectManagement\Domain\Exception\ProjectHasLinkedEntitiesException;
use App\ProjectManagement\Domain\Exception\ProjectNotFoundException;
use App\ProjectManagement\Infrastructure\Transformer\DeleteProjectInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер удаления проекта. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Delete(
    summary: 'Удалить проект',
    description: 'Выполняет мягкое удаление проекта.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор проекта.',
)]
#[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Проект успешно удалён.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Проект не найден.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/project-management/projects/{id}', name: 'project_management_delete_project', methods: ['DELETE'])]
final class DeleteProjectController extends AbstractController
{
    public function __construct(
        private readonly DeleteProjectUseCase $useCase,
        private readonly DeleteProjectInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (ProjectNotFoundException) {
            return new JsonResponse(['error' => 'Проект не найден.'], Response::HTTP_NOT_FOUND);
        } catch (ProjectHasLinkedEntitiesException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
