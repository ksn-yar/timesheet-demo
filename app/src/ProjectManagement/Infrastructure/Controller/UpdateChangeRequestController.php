<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\UpdateChangeRequestUseCase;
use App\ProjectManagement\Domain\Exception\ChangeRequestNotFoundException;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Infrastructure\Dto\UpdateChangeRequestRequestDto;
use App\ProjectManagement\Infrastructure\Transformer\UpdateChangeRequestInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления запроса на изменение. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Put(
    summary: 'Обновить запрос на изменение',
    description: 'Обновляет данные существующего запроса на изменение.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор запроса на изменение.')]
#[OA\RequestBody(required: true, content: new OA\JsonContent(ref: UpdateChangeRequestRequestDto::class))]
#[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Запрос на изменение успешно обновлён.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Запрос на изменение не найден.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/project-management/change-requests/{id}', name: 'project_management_update_change_request', methods: ['PUT'])]
final class UpdateChangeRequestController extends AbstractController
{
    public function __construct(
        private readonly UpdateChangeRequestUseCase $useCase,
        private readonly UpdateChangeRequestInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateChangeRequestRequestDto::class)]
        UpdateChangeRequestRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (ChangeRequestNotFoundException) {
            return new JsonResponse(['error' => 'Запрос на изменение не найден.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
