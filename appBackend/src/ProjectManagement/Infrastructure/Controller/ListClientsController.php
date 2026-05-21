<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\ListClientsUseCase;
use App\ProjectManagement\Infrastructure\Dto\ClientListResponseDto;
use App\ProjectManagement\Infrastructure\Dto\ListClientsRequestDto;
use App\ProjectManagement\Infrastructure\Presenter\HttpListClientsPresenter;
use App\ProjectManagement\Infrastructure\Transformer\ListClientsInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка клиентов. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Get(
    summary: 'Получить список клиентов',
    description: 'Возвращает список клиентов с пагинацией и фильтрацией.',
)]
#[OA\Parameter(name: 'name', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Фильтр по названию.')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список клиентов.',
    content: new OA\JsonContent(ref: ClientListResponseDto::class),
)]
#[Route('/api/project-management/clients', name: 'project_management_list_clients', methods: ['GET'])]
final class ListClientsController extends AbstractController
{
    public function __construct(
        private readonly ListClientsUseCase $useCase,
        private readonly ListClientsInputTransformer $transformer,
        private readonly HttpListClientsPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListClientsRequestDto::class)]
        ListClientsRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
