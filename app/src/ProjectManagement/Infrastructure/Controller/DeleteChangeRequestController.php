<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\DeleteChangeRequestUseCase;
use App\ProjectManagement\Domain\Exception\ChangeRequestHasLinkedTasksException;
use App\ProjectManagement\Domain\Exception\ChangeRequestNotFoundException;
use App\ProjectManagement\Infrastructure\Transformer\DeleteChangeRequestInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер удаления запроса на изменение. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Delete(
    summary: 'Удалить запрос на изменение',
    description: 'Выполняет мягкое удаление запроса на изменение.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор запроса на изменение.')]
#[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Запрос на изменение успешно удалён.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Запрос на изменение не найден.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/project-management/change-requests/{id}', name: 'project_management_delete_change_request', methods: ['DELETE'])]
final class DeleteChangeRequestController extends AbstractController
{
    public function __construct(
        private readonly DeleteChangeRequestUseCase $useCase,
        private readonly DeleteChangeRequestInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (ChangeRequestNotFoundException) {
            return new JsonResponse(['error' => 'Запрос на изменение не найден.'], Response::HTTP_NOT_FOUND);
        } catch (ChangeRequestHasLinkedTasksException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
