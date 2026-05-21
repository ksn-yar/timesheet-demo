<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\CreateTaskUseCase;
use App\ProjectManagement\Domain\Exception\ChangeRequestNotFoundException;
use App\ProjectManagement\Domain\Exception\InvalidTaskParentException;
use App\ProjectManagement\Domain\Exception\ProjectNotFoundException;
use App\ProjectManagement\Infrastructure\Dto\CreateTaskRequestDto;
use App\ProjectManagement\Infrastructure\Transformer\CreateTaskInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания задачи. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Post(
    summary: 'Создать задачу',
    description: 'Создаёт новую задачу для проекта или запроса на изменение.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateTaskRequestDto::class)),
)]
#[OA\Response(response: Response::HTTP_CREATED, description: 'Задача успешно создана.')]
#[OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Невалидные данные запроса.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Родительская сущность не найдена.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/project-management/tasks', name: 'project_management_create_task', methods: ['POST'])]
final class CreateTaskController extends AbstractController
{
    public function __construct(
        private readonly CreateTaskUseCase $useCase,
        private readonly CreateTaskInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateTaskRequestDto::class)]
        CreateTaskRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (ProjectNotFoundException) {
            return new JsonResponse(['error' => 'Проект не найден.'], Response::HTTP_NOT_FOUND);
        } catch (ChangeRequestNotFoundException) {
            return new JsonResponse(['error' => 'Запрос на изменение не найден.'], Response::HTTP_NOT_FOUND);
        } catch (InvalidTaskParentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
