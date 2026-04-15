<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\UpdateProjectUseCase;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Domain\Exception\ProjectNotFoundException;
use App\ProjectManagement\Infrastructure\Dto\UpdateProjectRequestDto;
use App\ProjectManagement\Infrastructure\Transformer\UpdateProjectInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления проекта. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Put(
    summary: 'Обновить проект',
    description: 'Обновляет данные существующего проекта.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор проекта.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: UpdateProjectRequestDto::class)),
)]
#[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Проект успешно обновлён.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Проект не найден.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/project-management/projects/{id}', name: 'project_management_update_project', methods: ['PUT'])]
final class UpdateProjectController extends AbstractController
{
    public function __construct(
        private readonly UpdateProjectUseCase $useCase,
        private readonly UpdateProjectInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateProjectRequestDto::class)]
        UpdateProjectRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (ProjectNotFoundException) {
            return new JsonResponse(['error' => 'Проект не найден.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
