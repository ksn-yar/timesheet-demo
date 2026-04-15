<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на получение списка тикетов с фильтрацией. */
final readonly class ListTicketsRequestDto
{
    public function __construct(
        #[Assert\Uuid(message: 'Идентификатор сотрудника должен быть валидным UUID.')]
        public ?string $employeeId = null,
        #[Assert\Uuid(message: 'Идентификатор проекта должен быть валидным UUID.')]
        public ?string $projectId = null,
        #[Assert\Uuid(message: 'Идентификатор CR должен быть валидным UUID.')]
        public ?string $crId = null,
        #[Assert\Uuid(message: 'Идентификатор задачи должен быть валидным UUID.')]
        public ?string $taskId = null,
        #[Assert\Uuid(message: 'Идентификатор вида работ должен быть валидным UUID.')]
        public ?string $workId = null,
        #[Assert\Date(message: 'Дата начала должна быть в формате YYYY-MM-DD.')]
        public ?string $dateFrom = null,
        #[Assert\Date(message: 'Дата окончания должна быть в формате YYYY-MM-DD.')]
        public ?string $dateTo = null,
        #[Assert\Positive(message: 'Номер страницы должен быть положительным числом.')]
        public int $page = 1,
        #[Assert\Range(
            min: 1,
            max: 100,
            notInRangeMessage: 'Количество элементов на странице должно быть от {{ min }} до {{ max }}.',
        )]
        public int $perPage = 20,
    ) {}
}
