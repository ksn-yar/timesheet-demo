<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Controller;

use App\Reporting\Application\UseCase\ExportReportsUseCase;
use App\Reporting\Domain\Exception\ReportExportGenerationException;
use App\Reporting\Domain\Exception\ReportNotFoundException;
use App\Reporting\Infrastructure\Dto\ExportReportsRequestDto;
use App\Reporting\Infrastructure\Transformer\ExportReportsInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер экспорта набора отчётов в файл заданного формата. */
#[OA\Tag(name: 'Reporting')]
#[OA\Post(
    summary: 'Экспортировать отчёты',
    description: 'Генерирует файл экспорта для набора отчётов в заданном формате.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: ExportReportsRequestDto::class),
)]
#[OA\Response(
    response: Response::HTTP_CREATED,
    description: 'Экспорт успешно создан.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Один из отчётов не найден.',
)]
#[OA\Response(
    response: Response::HTTP_UNPROCESSABLE_ENTITY,
    description: 'Ошибка генерации файла.',
)]
#[Route('/api/reporting/exports', name: 'reporting_export_reports', methods: ['POST'])]
final class ExportReportsController extends AbstractController
{
    public function __construct(
        private readonly ExportReportsUseCase $useCase,
        private readonly ExportReportsInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(ExportReportsRequestDto::class)]
        ExportReportsRequestDto $dto,
    ): JsonResponse {
        try {
            $inputDto = $this->transformer->transform($dto);
            $this->useCase->execute($inputDto);
        } catch (ReportNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (ReportExportGenerationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(['exportId' => $inputDto->exportId], Response::HTTP_CREATED);
    }
}
