<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Http\Controller;

use App\Timesheet\Application\UseCase\CreateImportPolicyUseCase;
use App\Timesheet\Infrastructure\Dto\CreateImportPolicyRequestDto;
use App\Timesheet\Infrastructure\Transformer\CreateImportPolicyInputTransformer;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания политики импорта тикетов. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Post(
    summary: 'Создать политику импорта',
    description: 'Создаёт новую политику импорта тикетов из внешней системы.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: CreateImportPolicyRequestDto::class),
)]
#[OA\Response(response: Response::HTTP_CREATED, description: 'Политика импорта успешно создана.')]
#[OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Невалидные данные.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/timesheet/import-policies', name: 'timesheet_create_import_policy', methods: ['POST'])]
final class CreateImportPolicyController extends AbstractController
{
    public function __construct(
        private readonly CreateImportPolicyUseCase $useCase,
        private readonly CreateImportPolicyInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateImportPolicyRequestDto::class)]
        CreateImportPolicyRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
