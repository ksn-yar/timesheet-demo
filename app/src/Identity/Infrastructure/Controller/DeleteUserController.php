<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\DeleteUserUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserHasLinkedTicketsException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Infrastructure\Transformer\DeleteUserInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер удаления пользователя. */
#[OA\Tag(name: 'Identity')]
#[OA\Delete(
    summary: 'Удалить пользователя',
    description: 'Выполняет мягкое удаление пользователя.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор пользователя.',
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Пользователь успешно удалён.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Пользователь не найден.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/identity/users/{id}', name: 'identity_delete_user', methods: ['DELETE'])]
final class DeleteUserController extends AbstractController
{
    public function __construct(
        private readonly DeleteUserUseCaseInterface $useCase,
        private readonly DeleteUserInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (UserNotFoundException) {
            return new JsonResponse(['error' => 'Пользователь не найден.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException | UserHasLinkedTicketsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
