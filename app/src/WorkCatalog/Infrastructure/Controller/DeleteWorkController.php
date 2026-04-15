<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\DeleteWorkUseCase;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\WorkHasLinkedTicketsException;
use App\WorkCatalog\Domain\Exception\WorkNotFoundException;
use App\WorkCatalog\Infrastructure\Transformer\DeleteWorkInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер удаления вида работ. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Delete(
    summary: 'Удалить вид работ',
    description: 'Выполняет мягкое удаление вида работ.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор вида работ.',
)]
#[OA\Response(
    response: Response::HTTP_NO_CONTENT,
    description: 'Вид работ успешно удалён.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Вид работ не найден.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/work-catalog/works/{id}', name: 'work_catalog_delete_work', methods: ['DELETE'])]
#[IsGranted('ROLE_ADMIN')]
final class DeleteWorkController extends AbstractController
{
    public function __construct(
        private readonly DeleteWorkUseCase $useCase,
        private readonly DeleteWorkInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $this->useCase->execute($this->transformer->transform($id));
        } catch (WorkNotFoundException) {
            return new JsonResponse(['error' => 'Вид работ не найден.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException | WorkHasLinkedTicketsException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
