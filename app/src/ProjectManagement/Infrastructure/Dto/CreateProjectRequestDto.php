<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use App\ProjectManagement\Domain\Enum\ProjectStatus;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание проекта. */
final readonly class CreateProjectRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Идентификатор клиента обязателен.')]
        #[Assert\Uuid(message: 'Идентификатор клиента должен быть валидным UUID.')]
        public string $clientId,
        #[Assert\NotBlank(message: 'Название проекта обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Название проекта не должно превышать 255 символов.')]
        public string $name,
        #[Assert\NotBlank(message: 'Статус проекта обязателен.')]
        #[Assert\Choice(callback: [ProjectStatus::class, 'values'], message: 'Недопустимое значение статуса проекта.')]
        public string $status,
        #[Assert\Length(max: 1000, maxMessage: 'Описание не должно превышать 1000 символов.')]
        public ?string $description = null,
    ) {}
}
