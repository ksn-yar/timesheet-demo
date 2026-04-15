<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Http\Controller;

use App\Timesheet\Application\UseCase\UpdateManualTicketUseCase;
use App\Timesheet\Domain\Exception\InvalidTicketHoursException;
use App\Timesheet\Domain\Exception\TicketNotEditableException;
use App\Timesheet\Domain\Exception\TicketNotFoundException;
use App\Timesheet\Domain\Exception\TicketOwnershipViolationException;
use App\Timesheet\Infrastructure\Dto\UpdateManualTicketRequestDto;
use App\Timesheet\Infrastructure\Transformer\UpdateManualTicketInputTransformer;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер обновления тикета учёта времени. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Put(
    summary: 'Обновить тикет',
    description: 'Обновляет данные существующего тикета учёта времени.',
)]
#[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid'), description: 'Идентификатор тикета.')]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: UpdateManualTicketRequestDto::class)),
)]
#[OA\Response(response: Response::HTTP_NO_CONTENT, description: 'Тикет успешно обновлён.')]
#[OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Невалидные данные.')]
#[OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Нарушение прав доступа.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Тикет не найден.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/api/timesheet/tickets/{id}', name: 'timesheet_update_ticket', methods: ['PUT'])]
final class UpdateManualTicketController extends AbstractController
{
    public function __construct(
        private readonly UpdateManualTicketUseCase $useCase,
        private readonly UpdateManualTicketInputTransformer $transformer,
    ) {}

    public function __invoke(
        string $id,
        #[ValueResolver(UpdateManualTicketRequestDto::class)]
        UpdateManualTicketRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($id, $dto));
        } catch (TicketNotFoundException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (TicketOwnershipViolationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (InvalidTicketHoursException | TicketNotEditableException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
