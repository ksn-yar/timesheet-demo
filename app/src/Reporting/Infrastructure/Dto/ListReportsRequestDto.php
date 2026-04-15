<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего GET-запроса для получения списка отчётов с фильтрацией и пагинацией. */
final readonly class ListReportsRequestDto
{
    public function __construct(
        #[Assert\Uuid(message: 'Идентификатор создателя должен быть валидным UUID.')]
        public ?string $createdBy = null,
        #[Assert\Date(message: 'Дата начала периода должна быть в формате Y-m-d.')]
        public ?string $periodFrom = null,
        #[Assert\Date(message: 'Дата окончания периода должна быть в формате Y-m-d.')]
        public ?string $periodTo = null,
        #[Assert\Length(max: 255, maxMessage: 'Название не должно превышать 255 символов.')]
        public ?string $name = null,
        #[Assert\Positive(message: 'Номер страницы должен быть положительным числом.')]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'Количество элементов на странице должно быть от 1 до 100.')]
        public int $perPage = 20,
    ) {}
}
