<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\CreateUserUseCaseInterface;
use App\Identity\Domain\Exception\DuplicateEmailException;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Infrastructure\Dto\CreateUserRequestDto;
use App\Identity\Infrastructure\Transformer\CreateUserInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания пользователя. */
#[OA\Tag(name: 'Identity')]
#[OA\Post(
    summary: 'Создать пользователя',
    description: 'Создаёт нового пользователя.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateUserRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Пользователь успешно создан.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/identity/users', name: 'identity_create_user', methods: ['POST'])]
final class CreateUserController extends AbstractController
{
    public function __construct(
        private readonly CreateUserUseCaseInterface $useCase,
        private readonly CreateUserInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateUserRequestDto::class)]
        CreateUserRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (DuplicateEmailException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (GroupNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
