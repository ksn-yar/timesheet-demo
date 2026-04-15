<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Controller;

use App\Reporting\Domain\Exception\ReportExportNotFoundException;
use App\Reporting\Domain\Repository\ReportExportRepositoryInterface;
use App\Reporting\Domain\ValueObject\ReportExportId;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер скачивания файла выгрузки отчётов. */
#[OA\Tag(name: 'Reporting')]
#[OA\Get(
    summary: 'Скачать файл выгрузки',
    description: 'Возвращает файл выгрузки для указанной записи экспорта.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор выгрузки.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Файл выгрузки.',
)]
#[OA\Response(
    response: Response::HTTP_NOT_FOUND,
    description: 'Выгрузка не найдена.',
)]
#[Route('/api/reporting/exports/{id}/download', name: 'reporting_download_export', methods: ['GET'])]
final class DownloadExportController extends AbstractController
{
    public function __construct(
        private readonly ReportExportRepositoryInterface $exportRepository,
        private readonly KernelInterface $kernel,
    ) {}

    public function __invoke(string $id): Response
    {
        try {
            $exportId = new ReportExportId($id);
        } catch (InvalidArgumentException) {
            return new JsonResponse(['error' => 'Некорректный идентификатор выгрузки.'], Response::HTTP_BAD_REQUEST);
        }

        $export = $this->exportRepository->findById($exportId);

        if (null === $export) {
            return new JsonResponse(
                ['error' => new ReportExportNotFoundException($id)->getMessage()],
                Response::HTTP_NOT_FOUND,
            );
        }

        $absolutePath = $this->kernel->getProjectDir() . '/' . $export->fileRef();

        $response = new BinaryFileResponse($absolutePath);
        $response->headers->set('Content-Type', $export->format()->getContentType());

        return $response;
    }
}
