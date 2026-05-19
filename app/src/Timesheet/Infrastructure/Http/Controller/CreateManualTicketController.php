<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Http\Controller;

use App\Timesheet\Application\UseCase\CreateManualTicketUseCase;
use App\Timesheet\Domain\Exception\InvalidTicketHoursException;
use App\Timesheet\Domain\Exception\TicketOwnershipViolationException;
use App\Timesheet\Infrastructure\Dto\CreateManualTicketRequestDto;
use App\Timesheet\Infrastructure\Transformer\CreateManualTicketInputTransformer;
use DomainException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер создания тикета учёта времени. */
#[OA\Tag(name: 'Timesheet')]
#[OA\Post(
    summary: 'Создать тикет',
    description: 'Создаёт новый тикет учёта времени.',
)]
#[OA\RequestBody(
    required: true,
    content: new OA\JsonContent(ref: new OA\Schema(type: CreateManualTicketRequestDto::class)),
)]
#[OA\Response(response: Response::HTTP_CREATED, description: 'Тикет успешно создан.')]
#[OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Невалидные данные.')]
#[OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Нарушение прав доступа.')]
#[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Задача или вид работ не найдены.')]
#[OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Нарушение бизнес-правил.')]
#[Route('/timesheet/tickets', name: 'timesheet_create_ticket', methods: ['POST'])]
final class CreateManualTicketController extends AbstractController
{
    public function __construct(
        private readonly CreateManualTicketUseCase $useCase,
        private readonly CreateManualTicketInputTransformer $transformer,
    ) {}

    public function __invoke(
        #[ValueResolver(CreateManualTicketRequestDto::class)]
        CreateManualTicketRequestDto $dto,
    ): JsonResponse {
        try {
            $this->useCase->execute($this->transformer->transform($dto));
        } catch (TicketOwnershipViolationException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (InvalidTicketHoursException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_CREATED);
    }
}
