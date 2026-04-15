<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание ставки. */
final readonly class CreateRateRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Сумма обязательна.')]
        #[Assert\Positive(message: 'Сумма должна быть положительным числом.')]
        public string $amount,
        #[Assert\NotBlank(message: 'Валюта обязательна.')]
        #[Assert\Length(exactly: 3, exactMessage: 'Код валюты должен содержать ровно 3 символа.')]
        public string $currency,
        #[Assert\NotBlank(message: 'Дата начала действия обязательна.')]
        #[Assert\Date(message: 'Некорректный формат даты. Ожидается YYYY-MM-DD.')]
        public string $effectiveFrom,
        #[Assert\Uuid(message: 'Некорректный формат UUID роли.')]
        public ?string $roleId = null,
        #[Assert\Uuid(message: 'Некорректный формат UUID вида работ.')]
        public ?string $workId = null,
    ) {}
}
