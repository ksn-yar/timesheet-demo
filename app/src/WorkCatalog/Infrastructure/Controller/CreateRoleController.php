<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\CreateRoleUseCase;
use App\WorkCatalog\Domain\Exception\DuplicateRoleNameException;
use App\WorkCatalog\Infrastructure\Dto\CreateRoleRequestDto;
use App\WorkCatalog\Infrastructure\Transformer\CreateRoleInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер создания роли. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Post(
    summary: 'Создать роль',
    description: 'Создаёт новую роль.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateRoleRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Роль успешно создана.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/work-catalog/roles', name: 'work_catalog_create_role', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreateRoleController extends AbstractController
{
    public function __construct(
        private readonly CreateRoleUseCase $useCase,
        private readonly CreateRoleInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateRoleRequestDto::class)]
        CreateRoleRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (DuplicateRoleNameException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
