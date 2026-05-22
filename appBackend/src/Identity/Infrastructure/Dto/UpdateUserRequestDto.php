<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use App\Identity\Domain\Enum\SystemRole;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на обновление пользователя (PUT-семантика). */
final readonly class UpdateUserRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Имя пользователя не может быть пустым.')]
        #[Assert\Length(max: 255, maxMessage: 'Имя пользователя не должно превышать 255 символов.')]
        public ?string $name = null,
        #[Assert\NotBlank(message: 'Системная роль не может быть пустой.')]
        #[Assert\Choice(callback: [SystemRole::class, 'values'], message: 'Недопустимое значение системной роли.')]
        public ?string $systemRole = null,
        #[Assert\Uuid(message: 'Некорректный формат UUID роли.')]
        public ?string $roleId = null,
    ) {}
}
