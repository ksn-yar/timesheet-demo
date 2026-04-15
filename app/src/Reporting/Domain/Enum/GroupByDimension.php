<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Enum;

/** Измерение группировки данных в отчёте. */
enum GroupByDimension: string
{
    case Employee = 'employee';
    case Group = 'group';
    case Project = 'project';
    case ChangeRequest = 'cr';
    case Task = 'task';
    case Work = 'work';

    public function getLabel(): string
    {
        return match ($this) {
            self::Employee => 'Сотрудник',
            self::Group => 'Группа',
            self::Project => 'Проект',
            self::ChangeRequest => 'CR',
            self::Task => 'Задача',
            self::Work => 'Вид работ',
        };
    }

    /** Возвращает массив допустимых значений для использования в Assert\Choice. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
