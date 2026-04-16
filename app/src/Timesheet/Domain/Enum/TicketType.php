<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Enum;

/** Перечисление типов тикета: ручной ввод или импорт из внешней системы. */
enum TicketType: string
{
    case Manual = 'manual';
    case Imported = 'imported';

    /** Возвращает человекочитаемую метку на русском языке. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Ручной',
            self::Imported => 'Импортированный',
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
