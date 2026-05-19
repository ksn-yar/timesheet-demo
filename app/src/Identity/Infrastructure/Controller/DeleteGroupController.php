<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\DeleteGroupUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupHasActiveUsersException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Infrastructure\Transformer\DeleteGroupInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер удаления группы. */
#[OA\Tag(name: 'Identity')]
#[OA\Delete(
    summary: 'Удалить группу',
    description: 'Выполняет мягкое удаление группы.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор группы.',
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Группа успешно удалена.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Группа не найдена.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/identity/groups/{id}', name: 'identity_delete_group', methods: ['DELETE'])]
final class DeleteGroupController extends AbstractController
{
    public function __construct(
        private readonly DeleteGroupUseCaseInterface $useCase,
        private readonly DeleteGroupInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (GroupNotFoundException) {
            return new JsonResponse(['error' => 'Группа не найдена.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException | GroupHasActiveUsersException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
