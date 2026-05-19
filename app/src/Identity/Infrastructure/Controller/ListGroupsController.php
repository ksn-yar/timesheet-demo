<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\ListGroupsUseCaseInterface;
use App\Identity\Infrastructure\Dto\GroupListResponseDto;
use App\Identity\Infrastructure\Dto\ListGroupsRequestDto;
use App\Identity\Infrastructure\Presenter\ListGroupsPresenterInterface;
use App\Identity\Infrastructure\Transformer\ListGroupsInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка групп. */
#[OA\Tag(name: 'Identity')]
#[OA\Get(
    summary: 'Получить список групп',
    description: 'Возвращает список групп с пагинацией.',
)]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список групп.',
    content: new OA\JsonContent(ref: GroupListResponseDto::class),
)]
#[Route('/identity/groups', name: 'identity_list_groups', methods: ['GET'])]
final class ListGroupsController extends AbstractController
{
    public function __construct(
        private readonly ListGroupsUseCaseInterface $useCase,
        private readonly ListGroupsInputTransformer $transformer,
        private readonly ListGroupsPresenterInterface $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListGroupsRequestDto::class)]
        ListGroupsRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
