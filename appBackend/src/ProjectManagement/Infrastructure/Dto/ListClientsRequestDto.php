<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на получение списка клиентов с фильтрацией. */
final readonly class ListClientsRequestDto
{
    public function __construct(
        #[Assert\Length(max: 255, maxMessage: 'Фильтр по названию не должен превышать 255 символов.')]
        public ?string $name = null,
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
