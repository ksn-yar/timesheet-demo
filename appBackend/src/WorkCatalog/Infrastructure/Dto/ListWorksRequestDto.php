<?php

declare(strict_types=1);

namespace App\WorkCatalog\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на получение списка видов работ. */
final readonly class ListWorksRequestDto
{
    public function __construct(
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
