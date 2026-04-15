<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на запуск импорта тикетов. */
final readonly class RunImportRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Дата начала обязательна.')]
        #[Assert\Date(message: 'Дата начала должна быть в формате YYYY-MM-DD.')]
        public string $dateFrom,
        #[Assert\NotBlank(message: 'Дата окончания обязательна.')]
        #[Assert\Date(message: 'Дата окончания должна быть в формате YYYY-MM-DD.')]
        public string $dateTo,
    ) {}
}
