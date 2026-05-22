<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на получение списка пользователей с фильтрацией. */
final readonly class ListUsersRequestDto
{
    public function __construct(
        #[Assert\Uuid(message: 'Некорректный формат UUID группы.')]
        public ?string $groupId = null,
        #[Assert\Uuid(message: 'Некорректный формат UUID роли.')]
        public ?string $roleId = null,
        public ?bool $isActive = null,
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
