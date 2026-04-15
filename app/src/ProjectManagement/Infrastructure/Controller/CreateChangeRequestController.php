<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\CreateChangeRequestUseCase;
use App\ProjectManagement\Domain\Exception\ProjectNotFoundException;
use App\ProjectManagement\Infrastructure\Dto\CreateChangeRequestRequestDto;
use App\ProjectManagement\Infrastructure\Transformer\CreateChangeRequestInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания запроса на изменение. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Post(
    summary: 'Создать запрос на изменение',
    description: 'Создаёт новый запрос на изменение для указанного проекта.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateChangeRequestRequestDto::class)),
)]
#[OA\Response(response: Response::HTTP_CREATED, description: 'Запрос на изменение успешно создан.')]
#[OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Невалидные данные запроса.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Проект не найден.')]
#[Route('/api/project-management/change-requests', name: 'project_management_create_change_request', methods: ['POST'])]
final class CreateChangeRequestController extends AbstractController
{
    public function __construct(
        private readonly CreateChangeRequestUseCase $useCase,
        private readonly CreateChangeRequestInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateChangeRequestRequestDto::class)]
        CreateChangeRequestRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (ProjectNotFoundException) {
            return new JsonResponse(['error' => 'Проект не найден.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
