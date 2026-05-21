<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\UpdateRateUseCase;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\RateEffectiveFromImmutableException;
use App\WorkCatalog\Domain\Exception\RateNotFoundException;
use App\WorkCatalog\Domain\Exception\RoleNotFoundException;
use App\WorkCatalog\Domain\Exception\WorkNotFoundException;
use App\WorkCatalog\Infrastructure\Dto\UpdateRateRequestDto;
use App\WorkCatalog\Infrastructure\Transformer\UpdateRateInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер обновления ставки. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Put(
    summary: 'Обновить ставку',
    description: 'Обновляет данные существующей ставки.',
)]
#[OA\Parameter(
    name: 'id',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
    description: 'Идентификатор ставки.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: UpdateRateRequestDto::class),
)]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Ставка успешно обновлена.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Ставка, роль или вид работ не найдены.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/work-catalog/rates/{id}', name: 'work_catalog_update_rate', methods: ['PUT'])]
#[IsGranted('ROLE_ADMIN')]
final class UpdateRateController extends AbstractController
{
    public function __construct(
        private readonly UpdateRateUseCase $useCase,
        private readonly UpdateRateInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateRateRequestDto::class)]
        UpdateRateRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (RateNotFoundException | RoleNotFoundException | WorkNotFoundException) {
            return new JsonResponse(['error' => 'Сущность не найдена.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException | RateEffectiveFromImmutableException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_OK);
    }
}
