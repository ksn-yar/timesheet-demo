<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\ChangeUserGroupUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Infrastructure\Dto\ChangeUserGroupRequestDto;
use App\Identity\Infrastructure\Transformer\ChangeUserGroupInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер изменения группы пользователя. */
#[OA\Tag(name: 'Identity')]
#[OA\Patch(
    summary: 'Изменить группу пользователя',
    description: 'Назначает пользователя в группу или снимает из группы.',
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
    content: new OA\JsonContent(ref: new OA\Schema(type: ChangeUserGroupRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Группа пользователя успешно изменена.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Пользователь или группа не найдены.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/identity/users/{id}/group', name: 'identity_change_user_group', methods: ['PATCH'])]
final class ChangeUserGroupController extends AbstractController
{
    public function __construct(
        private readonly ChangeUserGroupUseCaseInterface $useCase,
        private readonly ChangeUserGroupInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(ChangeUserGroupRequestDto::class)]
        ChangeUserGroupRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (GroupNotFoundException | UserNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
