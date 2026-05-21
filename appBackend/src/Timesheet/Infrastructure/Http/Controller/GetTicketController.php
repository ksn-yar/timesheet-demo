<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Http\Controller;

use App\Timesheet\Application\UseCase\GetTicketUseCase;
use App\Timesheet\Domain\Exception\TicketNotFoundException;
use App\Timesheet\Domain\Exception\TicketOwnershipViolationException;
use App\Timesheet\Infrastructure\Dto\TicketResponseDto;
use App\Timesheet\Infrastructure\Transformer\GetTicketInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер получения тикета по идентификатору. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Get(
    summary: 'Получить тикет по ID',
    description: 'Возвращает данные тикета учёта времени по его идентификатору.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор тикета.')]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Данные тикета.',
    content: new OA\JsonContent(ref: TicketResponseDto::class),
)]
#[OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Нарушение прав доступа.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Тикет не найден.')]
#[Route('/api/timesheet/tickets/{id}', name: 'timesheet_get_ticket', methods: ['GET'])]
final class GetTicketController extends AbstractController
{
    public function __construct(
        private readonly GetTicketUseCase $useCase,
        private readonly GetTicketInputTransformer $transformer,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        try {
            $ticket = $this->useCase->execute($this->transformer->transform($id));
        } catch (TicketNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (TicketOwnershipViolationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }

        $responseDto = new TicketResponseDto(
            id: $ticket->getId()->value(),
            employeeId: $ticket->getEmployeeId(),
            employeeName: '',
            taskId: $ticket->getTaskId(),
            taskName: '',
            workId: $ticket->getWorkId(),
            workName: '',
            date: $ticket->getDate()->format('Y-m-d'),
            hours: $ticket->getHours(),
            comment: $ticket->getComment(),
            rateSnapshot: $ticket->getRateSnapshot(),
            type: $ticket->getType()->value,
            importSource: $ticket->getImportSource(),
            externalId: $ticket->getExternalId(),
            isEditable: $ticket->isEditable(),
        );

        return $this->json($responseDto);
    }
}
