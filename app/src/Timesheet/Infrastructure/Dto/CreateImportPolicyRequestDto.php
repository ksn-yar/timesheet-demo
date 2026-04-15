<?php

declare(strict_types=1);

namespace App\Timesheet\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание политики импорта. */
final readonly class CreateImportPolicyRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название политики обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Название политики не должно превышать 255 символов.')]
        public string $name,
        #[Assert\NotBlank(message: 'Система-источник обязательна.')]
        #[Assert\Length(max: 255, maxMessage: 'Название системы-источника не должно превышать 255 символов.')]
        public string $sourceSystem,
        #[Assert\NotBlank(message: 'Правила маппинга обязательны.')]
        public array $mappingRules,
        #[Assert\NotNull(message: 'Флаг разрешения редактирования обязателен.')]
        public bool $allowEdit,
    ) {}
}
