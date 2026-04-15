<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Controller;

use App\WorkCatalog\Application\UseCase\CreateWorkUseCase;
use App\WorkCatalog\Domain\Exception\DuplicateWorkNameException;
use App\WorkCatalog\Infrastructure\Dto\CreateWorkRequestDto;
use App\WorkCatalog\Infrastructure\Transformer\CreateWorkInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Контроллер создания вида работ. */
#[OA\Tag(name: 'WorkCatalog')]
#[OA\Post(
    summary: 'Создать вид работ',
    description: 'Создаёт новый вид работ.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateWorkRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Вид работ успешно создан.',
)]
#[OA\Response(
    response: Response::HTTP_BAD_REQUEST,
    description: 'Невалидные данные запроса.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Нарушение бизнес-правил.',
)]
#[Route('/api/work-catalog/works', name: 'work_catalog_create_work', methods: ['POST'])]
#[IsGranted('ROLE_ADMIN')]
final class CreateWorkController extends AbstractController
{
    public function __construct(
        private readonly CreateWorkUseCase $useCase,
        private readonly CreateWorkInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateWorkRequestDto::class)]
        CreateWorkRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (DuplicateWorkNameException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
