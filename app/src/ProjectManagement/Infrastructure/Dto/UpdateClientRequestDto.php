<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на обновление клиента. */
final readonly class UpdateClientRequestDto
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true, message: 'Название клиента не может быть пустым.')]
        #[Assert\Length(max: 255, maxMessage: 'Название клиента не должно превышать 255 символов.')]
        public ?string $name = null,
        #[Assert\Length(max: 1000, maxMessage: 'Описание не должно превышать 1000 символов.')]
        public ?string $description = null,
    ) {}
}
