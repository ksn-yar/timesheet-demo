<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\DeleteRoleUseCase;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\RoleHasActiveRatesException;
use App\WorkCatalog\Domain\Exception\RoleHasLinkedUsersException;
use App\WorkCatalog\Domain\Exception\RoleNotFoundException;
use App\WorkCatalog\Infrastructure\Transformer\DeleteRoleInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер удаления роли. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Delete(
    summary: 'Удалить роль',
    description: 'Выполняет мягкое удаление роли.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор роли.',
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Роль успешно удалена.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Роль не найдена.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/work-catalog/roles/{id}', name: 'work_catalog_delete_role', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
final class DeleteRoleController extends AbstractController
{
    public function __construct(
        private readonly DeleteRoleUseCase $useCase,
        private readonly DeleteRoleInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (RoleNotFoundException) {
            return new JsonResponse(['error' => 'Роль не найдена.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException | RoleHasActiveRatesException | RoleHasLinkedUsersException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
