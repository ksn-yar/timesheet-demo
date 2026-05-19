<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Http\Controller;

use App\Timesheet\Application\UseCase\RunImportUseCase;
use App\Timesheet\Domain\Exception\ImportPolicyNotActiveException;
use App\Timesheet\Domain\Exception\ImportPolicyNotFoundException;
use App\Timesheet\Infrastructure\Dto\ImportSummaryResponseDto;
use App\Timesheet\Infrastructure\Dto\RunImportRequestDto;
use App\Timesheet\Infrastructure\Presenter\HttpRunImportPresenter;
use App\Timesheet\Infrastructure\Transformer\RunImportInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер запуска импорта тикетов по политике. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Post(
    summary: 'Запустить импорт',
    description: 'Запускает импорт тикетов по указанной политике импорта.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор политики импорта.')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: RunImportRequestDto::class)),
)]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Результат импорта.',
    content: new OA\JsonContent(ref: ImportSummaryResponseDto::class),
)]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Политика импорта не найдена.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Политика импорта неактивна.')]
#[Route('/timesheet/import-policies/{id}/run', name: 'timesheet_run_import', methods: ['POST'])]
final class RunImportController extends AbstractController
{
    public function __construct(
        private readonly RunImportUseCase $useCase,
        private readonly RunImportInputTransformer $transformer,
        private readonly HttpRunImportPresenter $presenter,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(RunImportRequestDto::class)]
        RunImportRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (ImportPolicyNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (ImportPolicyNotActiveException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->presenter->getResponseDto());
    }
}
