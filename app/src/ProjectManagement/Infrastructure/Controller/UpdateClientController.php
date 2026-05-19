<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Controller;

use App\ProjectManagement\Application\UseCase\UpdateClientUseCase;
use App\ProjectManagement\Domain\Exception\ClientNotFoundException;
use App\ProjectManagement\Domain\Exception\EntityDeletedException;
use App\ProjectManagement\Infrastructure\Dto\UpdateClientRequestDto;
use App\ProjectManagement\Infrastructure\Transformer\UpdateClientInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления клиента. */
#[OA\Tag(name: 'ProjectManagement')]
#[OA\Put(
    summary: 'Обновить клиента',
    description: 'Обновляет данные существующего клиента.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор клиента.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: UpdateClientRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Клиент успешно обновлён.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Клиент не найден.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/project-management/clients/{id}', name: 'project_management_update_client', methods: ['PUT'])]
final class UpdateClientController extends AbstractController
{
    public function __construct(
        private readonly UpdateClientUseCase $useCase,
        private readonly UpdateClientInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateClientRequestDto::class)]
        UpdateClientRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (ClientNotFoundException) {
            return new JsonResponse(['error' => 'Клиент не найден.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
