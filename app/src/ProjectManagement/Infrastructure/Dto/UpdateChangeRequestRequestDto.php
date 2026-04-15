<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на обновление запроса на изменение. */
final readonly class UpdateChangeRequestRequestDto
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true, message: 'Название запроса на изменение не может быть пустым.')]
        #[Assert\Length(max: 255, maxMessage: 'Название не должно превышать 255 символов.')]
        public ?string $name = null,
        #[Assert\Length(max: 1000, maxMessage: 'Описание не должно превышать 1000 символов.')]
        public ?string $description = null,
    ) {}
}
