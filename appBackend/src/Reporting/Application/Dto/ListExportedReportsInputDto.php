<?php

declare(strict_types=1);

namespace App\Reporting\Application\Dto;

/** Входные данные Use Case получения списка выгрузок с пагинацией и опциональной фильтрацией. */
final readonly class ListExportedReportsInputDto
{
    public function __construct(
        public ?string $format = null,
        public ?string $generatedAtFrom = null,
        public ?string $generatedAtTo = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
