<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\DeleteClientUseCase;
use App\ProjectManagement\Domain\Exception\ClientHasActiveProjectsException;
use App\ProjectManagement\Domain\Exception\ClientNotFoundException;
use App\ProjectManagement\Infrastructure\Transformer\DeleteClientInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер удаления клиента. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Delete(
    summary: 'Удалить клиента',
    description: 'Выполняет мягкое удаление клиента.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор клиента.',
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Клиент успешно удалён.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Клиент не найден.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/project-management/clients/{id}', name: 'project_management_delete_client', methods: ['DELETE'])]
final class DeleteClientController extends AbstractController
{
    public function __construct(
        private readonly DeleteClientUseCase $useCase,
        private readonly DeleteClientInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (ClientNotFoundException) {
            return new JsonResponse(['error' => 'Клиент не найден.'], Response::HTTP_NOT_FOUND);
        } catch (ClientHasActiveProjectsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
