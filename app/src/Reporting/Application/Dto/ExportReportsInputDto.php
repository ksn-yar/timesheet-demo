<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Входные данные Use Case экспорта набора отчётов в файл. UUID генерируется в InputTransformer. */
final readonly class ExportReportsInputDto
{
    /**
     * @param string[] $reportIds UUID-строки идентификаторов отчётов
     */
    public function __construct(
        public string $exportId,
        public array $reportIds,
        public string $format,
    ) {}
}
