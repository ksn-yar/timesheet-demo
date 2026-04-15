<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Enum;

/** Формат файла экспорта отчёта. Определяет тип генерируемого документа и его MIME-тип. */
enum ExportFormat: string
{
    case Csv = 'csv';
    case Xlsx = 'xlsx';
    case Pdf = 'pdf';

    public function getLabel(): string
    {
        return match ($this) {
            self::Csv => 'CSV',
            self::Xlsx => 'XLSX',
            self::Pdf => 'PDF',
        };
    }

    public function getExtension(): string
    {
        return match ($this) {
            self::Csv => '.csv',
            self::Xlsx => '.xlsx',
            self::Pdf => '.pdf',
        };
    }

    public function getContentType(): string
    {
        return match ($this) {
            self::Csv => 'text/csv',
            self::Xlsx => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            self::Pdf => 'application/pdf',
        };
    }

    /** Возвращает массив допустимых значений для использования в Assert\Choice. */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
