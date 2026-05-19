<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\UpdateGroupUseCaseInterface;
use App\Identity\Domain\Exception\DuplicateGroupNameException;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Infrastructure\Dto\UpdateGroupRequestDto;
use App\Identity\Infrastructure\Transformer\UpdateGroupInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления группы. */
#[OA\Tag(name: 'Identity')]
#[OA\Put(
    summary: 'Обновить группу',
    description: 'Обновляет данные существующей группы.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор группы.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: UpdateGroupRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Группа успешно обновлена.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Группа не найдена.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/identity/groups/{id}', name: 'identity_update_group', methods: ['PUT'])]
final class UpdateGroupController extends AbstractController
{
    public function __construct(
        private readonly UpdateGroupUseCaseInterface $useCase,
        private readonly UpdateGroupInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateGroupRequestDto::class)]
        UpdateGroupRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (GroupNotFoundException) {
            return new JsonResponse(['error' => 'Группа не найдена.'], Response::HTTP_NOT_FOUND);
        } catch (DuplicateGroupNameException | EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
