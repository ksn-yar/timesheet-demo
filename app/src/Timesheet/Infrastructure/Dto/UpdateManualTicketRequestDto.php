<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на обновление тикета. */
final readonly class UpdateManualTicketRequestDto
{
    public function __construct(
        #[Assert\Date(message: 'Дата должна быть в формате YYYY-MM-DD.')]
        public ?string $date = null,
        #[Assert\Positive(message: 'Количество часов должно быть положительным числом.')]
        public ?string $hours = null,
        #[Assert\Uuid(message: 'Идентификатор вида работ должен быть валидным UUID.')]
        public ?string $workId = null,
        #[Assert\Length(max: 1000, maxMessage: 'Комментарий не должен превышать 1000 символов.')]
        public ?string $comment = null,
    ) {}
}
