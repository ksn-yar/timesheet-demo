<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\ListRolesUseCase;
use App\WorkCatalog\Infrastructure\Dto\ListRolesRequestDto;
use App\WorkCatalog\Infrastructure\Dto\RoleListResponseDto;
use App\WorkCatalog\Infrastructure\Presenter\HttpListRolesPresenter;
use App\WorkCatalog\Infrastructure\Transformer\ListRolesInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер получения списка ролей. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Get(
    summary: 'Получить список ролей',
    description: 'Возвращает список ролей с пагинацией.',
)]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список ролей.',
    content: new OA\JsonContent(ref: RoleListResponseDto::class),
)]
#[Route('/work-catalog/roles', name: 'work_catalog_list_roles', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class ListRolesController extends AbstractController
{
    public function __construct(
        private readonly ListRolesUseCase $useCase,
        private readonly ListRolesInputTransformer $transformer,
        private readonly HttpListRolesPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListRolesRequestDto::class)]
        ListRolesRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
