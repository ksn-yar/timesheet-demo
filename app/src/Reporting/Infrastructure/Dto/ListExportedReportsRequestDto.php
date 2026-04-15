<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Dto;

use App\Reporting\Domain\Enum\ExportFormat;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего GET-запроса для получения списка выгрузок отчётов с фильтрацией и пагинацией. */
final readonly class ListExportedReportsRequestDto
{
    public function __construct(
        #[Assert\Choice(callback: [ExportFormat::class, 'values'], message: 'Недопустимый формат экспорта.')]
        public ?string $format = null,
        #[Assert\DateTime(message: 'Дата начала должна быть в формате ISO 8601.')]
        public ?string $generatedAtFrom = null,
        #[Assert\DateTime(message: 'Дата окончания должна быть в формате ISO 8601.')]
        public ?string $generatedAtTo = null,
        #[Assert\Positive(message: 'Номер страницы должен быть положительным числом.')]
        public int $page = 1,
        #[Assert\Range(min: 1, max: 100, notInRangeMessage: 'Количество элементов на странице должно быть от 1 до 100.')]
        public int $perPage = 20,
    ) {}
}
