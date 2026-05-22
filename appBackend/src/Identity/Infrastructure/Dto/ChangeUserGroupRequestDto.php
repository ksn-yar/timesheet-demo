<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на изменение группы пользователя. */
final readonly class ChangeUserGroupRequestDto
{
    public function __construct(
        #[Assert\Uuid(message: 'Некорректный формат UUID группы.')]
        public ?string $groupId = null,
    ) {}
}
