<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\UseCase\DeactivateUserUseCase;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserAlreadyDeactivatedException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Infrastructure\Transformer\DeactivateUserInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер деактивации пользователя. */
#[OA\Tag(name: 'Identity')]
#[OA\Post(
    summary: 'Деактивировать пользователя',
    description: 'Деактивирует учётную запись пользователя.',
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
    description: 'Пользователь успешно деактивирован.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Пользователь не найден.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/identity/users/{id}/deactivate', name: 'identity_deactivate_user', methods: ['POST'])]
final class DeactivateUserController extends AbstractController
{
    public function __construct(
        private readonly DeactivateUserUseCase $useCase,
        private readonly DeactivateUserInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (UserNotFoundException) {
            return new JsonResponse(['error' => 'Пользователь не найден.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException | UserAlreadyDeactivatedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
