<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание запроса на изменение. */
final readonly class CreateChangeRequestRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Идентификатор проекта обязателен.')]
        #[Assert\Uuid(message: 'Идентификатор проекта должен быть валидным UUID.')]
        public string $projectId,
        #[Assert\NotBlank(message: 'Название запроса на изменение обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Название не должно превышать 255 символов.')]
        public string $name,
        #[Assert\Length(max: 1000, maxMessage: 'Описание не должно превышать 1000 символов.')]
        public ?string $description = null,
    ) {}
}
