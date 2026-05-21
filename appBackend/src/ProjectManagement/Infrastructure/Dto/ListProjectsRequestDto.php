<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use App\ProjectManagement\Domain\Enum\ProjectStatus;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на получение списка проектов с фильтрацией. */
final readonly class ListProjectsRequestDto
{
    public function __construct(
        #[Assert\Uuid(message: 'Идентификатор клиента должен быть валидным UUID.')]
        public ?string $clientId = null,
        #[Assert\Choice(callback: [ProjectStatus::class, 'values'], message: 'Недопустимое значение статуса проекта.')]
        public ?string $status = null,
        #[Assert\Positive(message: 'Номер страницы должен быть положительным числом.')]
        public int $page = 1,
        #[Assert\Range(
            min: 1,
            max: 100,
            notInRangeMessage: 'Количество элементов на странице должно быть от {{ min }} до {{ max }}.',
        )]
        public int $perPage = 20,
    ) {}
}
