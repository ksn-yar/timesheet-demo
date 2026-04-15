<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use App\Identity\Domain\Enum\SystemRole;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание пользователя. */
final readonly class CreateUserRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Имя пользователя обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Имя пользователя не должно превышать 255 символов.')]
        public string $name,
        #[Assert\NotBlank(message: 'Email обязателен.')]
        #[Assert\Email(message: 'Некорректный формат email.')]
        #[Assert\Length(max: 255, maxMessage: 'Email не должен превышать 255 символов.')]
        public string $email,
        #[Assert\NotBlank(message: 'Пароль обязателен.')]
        #[Assert\Length(
            min: 6,
            max: 255,
            minMessage: 'Пароль должен содержать не менее 6 символов.',
            maxMessage: 'Пароль не должен превышать 255 символов.',
        )]
        public string $password,
        #[Assert\NotBlank(message: 'Системная роль обязательна.')]
        #[Assert\Choice(callback: [SystemRole::class, 'values'], message: 'Недопустимое значение системной роли.')]
        public string $systemRole,
        #[Assert\Uuid(message: 'Некорректный формат UUID группы.')]
        public ?string $groupId = null,
        #[Assert\Uuid(message: 'Некорректный формат UUID роли.')]
        public ?string $roleId = null,
    ) {}
}
