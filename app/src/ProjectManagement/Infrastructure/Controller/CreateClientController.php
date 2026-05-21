<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\CreateClientUseCase;
use App\ProjectManagement\Infrastructure\Dto\CreateClientRequestDto;
use App\ProjectManagement\Infrastructure\Transformer\CreateClientInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания клиента. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Post(
    summary: 'Создать клиента',
    description: 'Создаёт нового клиента.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateClientRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Клиент успешно создан.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[Route('/api/project-management/clients', name: 'project_management_create_client', methods: ['POST'])]
final class CreateClientController extends AbstractController
{
    public function __construct(
        private readonly CreateClientUseCase $useCase,
        private readonly CreateClientInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateClientRequestDto::class)]
        CreateClientRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
