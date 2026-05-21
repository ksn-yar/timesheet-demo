<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Элемент списка выгрузок отчётов. Представляет одну выгрузку с ссылкой на файл. */
final readonly class ExportItemDto
{
    /**
     * @param string[] $reportIds UUID-строки идентификаторов включённых отчётов
     */
    public function __construct(
        public string $id,
        public array $reportIds,
        public string $format,
        public string $generatedAt,
        public string $fileRef,
    ) {}
}
