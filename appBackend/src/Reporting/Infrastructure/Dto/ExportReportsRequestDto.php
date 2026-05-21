<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Dto;

use App\Reporting\Domain\Enum\ExportFormat;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на экспорт набора отчётов в файл. */
final readonly class ExportReportsRequestDto
{
    /** @param string[] $reportIds */
    public function __construct(
        #[Assert\NotBlank(message: 'Список идентификаторов отчётов обязателен.')]
        #[Assert\Count(min: 1, minMessage: 'Необходимо указать хотя бы один отчёт для экспорта.')]
        #[Assert\All([new Assert\Uuid(message: 'Каждый идентификатор отчёта должен быть валидным UUID.')])]
        public array $reportIds,
        #[Assert\NotBlank(message: 'Формат экспорта обязателен.')]
        #[Assert\Choice(callback: [ExportFormat::class, 'values'], message: 'Недопустимый формат экспорта.')]
        public string $format,
    ) {}
}
