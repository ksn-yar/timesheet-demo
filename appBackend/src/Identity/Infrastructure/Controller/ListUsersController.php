<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\ListUsersUseCaseInterface;
use App\Identity\Infrastructure\Dto\ListUsersRequestDto;
use App\Identity\Infrastructure\Dto\UserListResponseDto;
use App\Identity\Infrastructure\Presenter\ListUsersPresenterInterface;
use App\Identity\Infrastructure\Transformer\ListUsersInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка пользователей. */
#[OA\Tag(name: 'Identity')]
#[OA\Get(
    summary: 'Получить список пользователей',
    description: 'Возвращает список пользователей с пагинацией и фильтрацией.',
)]
#[OA\Parameter(name: 'groupId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по группе.')]
#[OA\Parameter(name: 'roleId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по роли.')]
#[OA\Parameter(name: 'isActive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'Фильтр по активности.')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список пользователей.',
    content: new OA\JsonContent(ref: UserListResponseDto::class),
)]
#[Route('/api/identity/users', name: 'identity_list_users', methods: ['GET'])]
final class ListUsersController extends AbstractController
{
    public function __construct(
        private readonly ListUsersUseCaseInterface $useCase,
        private readonly ListUsersInputTransformer $transformer,
        private readonly ListUsersPresenterInterface $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListUsersRequestDto::class)]
        ListUsersRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
