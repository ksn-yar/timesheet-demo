<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\DeleteRateUseCase;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\RateNotFoundException;
use App\WorkCatalog\Infrastructure\Transformer\DeleteRateInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер удаления ставки. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Delete(
    summary: 'Удалить ставку',
    description: 'Выполняет мягкое удаление ставки.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор ставки.',
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Ставка успешно удалена.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Ставка не найдена.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/work-catalog/rates/{id}', name: 'work_catalog_delete_rate', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
final class DeleteRateController extends AbstractController
{
    public function __construct(
        private readonly DeleteRateUseCase $useCase,
        private readonly DeleteRateInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (RateNotFoundException) {
            return new JsonResponse(['error' => 'Ставка не найдена.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
