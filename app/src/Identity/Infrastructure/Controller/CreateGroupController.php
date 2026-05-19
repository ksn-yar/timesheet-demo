<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\CreateGroupUseCaseInterface;
use App\Identity\Domain\Exception\DuplicateGroupNameException;
use App\Identity\Infrastructure\Dto\CreateGroupRequestDto;
use App\Identity\Infrastructure\Transformer\CreateGroupInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания группы. */
#[OA\Tag(name: 'Identity')]
#[OA\Post(
    summary: 'Создать группу',
    description: 'Создаёт новую группу пользователей.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateGroupRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Группа успешно создана.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/identity/groups', name: 'identity_create_group', methods: ['POST'])]
final class CreateGroupController extends AbstractController
{
    public function __construct(
        private readonly CreateGroupUseCaseInterface $useCase,
        private readonly CreateGroupInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateGroupRequestDto::class)]
        CreateGroupRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (DuplicateGroupNameException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
