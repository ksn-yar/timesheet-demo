<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Http\Controller;

use App\Timesheet\Application\UseCase\UpdateImportPolicyUseCase;
use App\Timesheet\Domain\Exception\ImportPolicyConflictException;
use App\Timesheet\Domain\Exception\ImportPolicyNotFoundException;
use App\Timesheet\Infrastructure\Dto\UpdateImportPolicyRequestDto;
use App\Timesheet\Infrastructure\Transformer\UpdateImportPolicyInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления политики импорта тикетов. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Put(
    summary: 'Обновить политику импорта',
    description: 'Обновляет данные существующей политики импорта.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор политики импорта.')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: UpdateImportPolicyRequestDto::class),
)]
#[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Политика импорта успешно обновлена.')]
#[OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Невалидные данные.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Политика импорта не найдена.')]
#[OA\Response(response: Response::HTTP_CONFLICT, description: 'Конфликт при обновлении политики.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/timesheet/import-policies/{id}', name: 'timesheet_update_import_policy', methods: ['PUT'])]
final class UpdateImportPolicyController extends AbstractController
{
    public function __construct(
        private readonly UpdateImportPolicyUseCase $useCase,
        private readonly UpdateImportPolicyInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateImportPolicyRequestDto::class)]
        UpdateImportPolicyRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (ImportPolicyNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (ImportPolicyConflictException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
