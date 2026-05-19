<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\CreateProjectUseCase;
use App\ProjectManagement\Domain\Exception\ClientNotFoundException;
use App\ProjectManagement\Infrastructure\Dto\CreateProjectRequestDto;
use App\ProjectManagement\Infrastructure\Transformer\CreateProjectInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания проекта. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Post(
    summary: 'Создать проект',
    description: 'Создаёт новый проект для указанного клиента.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateProjectRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Проект успешно создан.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Клиент не найден.',
)]
#[Route('/project-management/projects', name: 'project_management_create_project', methods: ['POST'])]
final class CreateProjectController extends AbstractController
{
    public function __construct(
        private readonly CreateProjectUseCase $useCase,
        private readonly CreateProjectInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateProjectRequestDto::class)]
        CreateProjectRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (ClientNotFoundException) {
            return new JsonResponse(['error' => 'Клиент не найден.'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
