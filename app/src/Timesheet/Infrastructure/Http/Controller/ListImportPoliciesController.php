<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Http\Controller;

use App\Timesheet\Application\UseCase\ListImportPoliciesUseCase;
use App\Timesheet\Infrastructure\Dto\ImportPolicyListResponseDto;
use App\Timesheet\Infrastructure\Dto\ListImportPoliciesRequestDto;
use App\Timesheet\Infrastructure\Presenter\HttpListImportPoliciesPresenter;
use App\Timesheet\Infrastructure\Transformer\ListImportPoliciesInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка политик импорта. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Get(
    summary: 'Получить список политик импорта',
    description: 'Возвращает список политик импорта с пагинацией.',
)]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список политик импорта.',
    content: new OA\JsonContent(ref: ImportPolicyListResponseDto::class),
)]
#[Route('/timesheet/import-policies', name: 'timesheet_list_import_policies', methods: ['GET'])]
final class ListImportPoliciesController extends AbstractController
{
    public function __construct(
        private readonly ListImportPoliciesUseCase $useCase,
        private readonly ListImportPoliciesInputTransformer $transformer,
        private readonly HttpListImportPoliciesPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListImportPoliciesRequestDto::class)]
        ListImportPoliciesRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
