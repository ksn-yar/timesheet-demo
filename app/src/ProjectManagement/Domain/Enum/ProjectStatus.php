<?php

declare(strict_types=1);

namespace App\ProjectManagement\Domain\Enum;

/** Перечисление статусов проекта. */
enum ProjectStatus: string
{
    case Active = 'active';
    case Closed = 'closed';

    /** Возвращает человекочитаемую метку на русском языке. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Активный',
            self::Closed => 'Закрыт',
        };
    }

    /**
     * Возвращает массив всех backed-значений для валидации.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
