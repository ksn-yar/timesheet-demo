<?php

declare(strict_types=1);

namespace App\Identity\Domain\Enum;

/** Перечисление системных ролей пользователя. */
enum SystemRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Employee = 'employee';

    /** Возвращает человекочитаемую метку на русском языке. */
    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Manager => 'Менеджер',
            self::Employee => 'Сотрудник',
        };
    }

    /** Возвращает массив всех backed-значений для валидации. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
