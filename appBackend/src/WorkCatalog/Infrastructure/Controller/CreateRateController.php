<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\CreateRateUseCase;
use App\WorkCatalog\Domain\Exception\EntityDeletedException;
use App\WorkCatalog\Domain\Exception\RoleNotFoundException;
use App\WorkCatalog\Domain\Exception\WorkNotFoundException;
use App\WorkCatalog\Infrastructure\Dto\CreateRateRequestDto;
use App\WorkCatalog\Infrastructure\Transformer\CreateRateInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер создания ставки. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Post(
    summary: 'Создать ставку',
    description: 'Создаёт новую ставку.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: CreateRateRequestDto::class),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Ставка успешно создана.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Роль или вид работ не найдены.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/work-catalog/rates', name: 'work_catalog_create_rate', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreateRateController extends AbstractController
{
    public function __construct(
        private readonly CreateRateUseCase $useCase,
        private readonly CreateRateInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateRateRequestDto::class)]
        CreateRateRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (RoleNotFoundException | WorkNotFoundException) {
            return new JsonResponse(['error' => 'Связанная сущность не найдена.'], Response::HTTP_NOT_FOUND);
        } catch (EntityDeletedException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
