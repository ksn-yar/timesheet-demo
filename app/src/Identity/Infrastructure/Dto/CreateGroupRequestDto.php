<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание группы. */
final readonly class CreateGroupRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Название группы обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Название группы не должно превышать 255 символов.')]
        public string $name,
        #[Assert\Length(max: 1000, maxMessage: 'Описание не должно превышать 1000 символов.')]
        public ?string $description = null,
    ) {}
}
