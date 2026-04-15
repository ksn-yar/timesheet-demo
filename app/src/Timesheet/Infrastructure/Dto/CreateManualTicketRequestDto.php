<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание тикета вручную. */
final readonly class CreateManualTicketRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Идентификатор сотрудника обязателен.')]
        #[Assert\Uuid(message: 'Идентификатор сотрудника должен быть валидным UUID.')]
        public string $employeeId,
        #[Assert\NotBlank(message: 'Идентификатор задачи обязателен.')]
        #[Assert\Uuid(message: 'Идентификатор задачи должен быть валидным UUID.')]
        public string $taskId,
        #[Assert\NotBlank(message: 'Идентификатор вида работ обязателен.')]
        #[Assert\Uuid(message: 'Идентификатор вида работ должен быть валидным UUID.')]
        public string $workId,
        #[Assert\NotBlank(message: 'Дата обязательна.')]
        #[Assert\Date(message: 'Дата должна быть в формате YYYY-MM-DD.')]
        public string $date,
        #[Assert\NotBlank(message: 'Количество часов обязательно.')]
        #[Assert\Positive(message: 'Количество часов должно быть положительным числом.')]
        public string $hours,
        #[Assert\Length(max: 1000, maxMessage: 'Комментарий не должен превышать 1000 символов.')]
        public ?string $comment = null,
    ) {}
}
