<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на обновление ставки. */
final readonly class UpdateRateRequestDto
{
    public function __construct(
        #[Assert\Positive(message: 'Сумма должна быть положительным числом.')]
        public ?string $amount = null,
        #[Assert\Length(exactly: 3, exactMessage: 'Код валюты должен содержать ровно 3 символа.')]
        public ?string $currency = null,
        #[Assert\Date(message: 'Некорректный формат даты. Ожидается YYYY-MM-DD.')]
        public ?string $effectiveFrom = null,
        #[Assert\Uuid(message: 'Некорректный формат UUID роли.')]
        public ?string $roleId = null,
        #[Assert\Uuid(message: 'Некорректный формат UUID вида работ.')]
        public ?string $workId = null,
    ) {}
}
