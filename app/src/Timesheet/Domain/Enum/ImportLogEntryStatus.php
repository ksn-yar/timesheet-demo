<?php

declare(strict_types=1);

namespace App\Timesheet\Domain\Enum;

/** Перечисление статусов записи лога импорта. */
enum ImportLogEntryStatus: string
{
    case Imported = 'imported';
    case Duplicate = 'duplicate';
    case Error = 'error';

    /** Возвращает человекочитаемую метку на русском языке. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Imported => 'Импортирован',
            self::Duplicate => 'Дубликат',
            self::Error => 'Ошибка',
        };
    }

    /** Возвращает массив всех backed-значений для валидации. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
