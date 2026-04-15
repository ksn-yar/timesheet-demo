<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Http\Controller;

use App\Timesheet\Application\UseCase\ListTicketsUseCase;
use App\Timesheet\Infrastructure\Dto\ListTicketsRequestDto;
use App\Timesheet\Infrastructure\Dto\TicketListResponseDto;
use App\Timesheet\Infrastructure\Presenter\HttpListTicketsPresenter;
use App\Timesheet\Infrastructure\Transformer\ListTicketsInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения списка тикетов с фильтрацией и пагинацией. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Get(
    summary: 'Получить список тикетов',
    description: 'Возвращает список тикетов учёта времени с фильтрацией и пагинацией.',
)]
#[OA\Parameter(name: 'employeeId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по сотруднику.')]
#[OA\Parameter(name: 'projectId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по проекту.')]
#[OA\Parameter(name: 'crId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по запросу на изменение.')]
#[OA\Parameter(name: 'taskId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по задаче.')]
#[OA\Parameter(name: 'workId', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Фильтр по виду работ.')]
#[OA\Parameter(name: 'dateFrom', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), description: 'Начальная дата фильтра.')]
#[OA\Parameter(name: 'dateTo', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), description: 'Конечная дата фильтра.')]
#[OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Номер страницы.')]
#[OA\Parameter(name: 'perPage', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Количество элементов на странице.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Список тикетов.',
    content: new OA\JsonContent(ref: TicketListResponseDto::class),
)]
#[Route('/api/timesheet/tickets', name: 'timesheet_list_tickets', methods: ['GET'])]
final class ListTicketsController extends AbstractController
{
    public function __construct(
        private readonly ListTicketsUseCase $useCase,
        private readonly ListTicketsInputTransformer $transformer,
        private readonly HttpListTicketsPresenter $presenter,
    ) {}

    public function __invoke(
        #[ValueResolver(ListTicketsRequestDto::class)]
        ListTicketsRequestDto $dto,
    ): JsonResponse {
        $this->useCase->execute($this->transformer->transform($dto));

        return $this->json($this->presenter->getResponseDto());
    }
}
