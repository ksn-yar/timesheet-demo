<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\UpdateUserUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Infrastructure\Dto\UpdateUserRequestDto;
use App\Identity\Infrastructure\Transformer\UpdateUserInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления пользователя. */
#[OA\Tag(name: 'Identity')]
#[OA\Put(
    summary: 'Обновить пользователя',
    description: 'Обновляет данные существующего пользователя.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор пользователя.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: UpdateUserRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Пользователь успешно обновлён.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Пользователь не найден.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/identity/users/{id}', name: 'identity_update_user', methods: ['PUT'])]
final class UpdateUserController extends AbstractController
{
    public function __construct(
        private readonly UpdateUserUseCaseInterface $useCase,
        private readonly UpdateUserInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateUserRequestDto::class)]
        UpdateUserRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (UserNotFoundException) {
            return new JsonResponse(['error' => 'Пользователь не найден.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
