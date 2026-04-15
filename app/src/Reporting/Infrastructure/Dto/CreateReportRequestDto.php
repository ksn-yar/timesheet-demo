<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Dto;

use App\Reporting\Domain\Enum\GroupByDimension;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание отчёта. */
final readonly class CreateReportRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название отчёта обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Название не должно превышать 255 символов.')]
        public string $name,
        #[Assert\NotBlank(message: 'Дата начала периода обязательна.')]
        #[Assert\Date(message: 'Дата начала периода должна быть в формате Y-m-d.')]
        public string $periodFrom,
        #[Assert\NotBlank(message: 'Дата окончания периода обязательна.')]
        #[Assert\Date(message: 'Дата окончания периода должна быть в формате Y-m-d.')]
        public string $periodTo,
        #[Assert\Valid]
        public ?array $filters = null,
        #[Assert\NotBlank(message: 'Измерения группировки обязательны.')]
        #[Assert\Count(min: 1, minMessage: 'Необходимо указать хотя бы одно измерение группировки.')]
        #[Assert\All([new Assert\Choice(callback: [GroupByDimension::class, 'values'], message: 'Недопустимое измерение группировки.')])]
        public array $groupBy = [],
    ) {}
}
