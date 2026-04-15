<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use App\ProjectManagement\Domain\Enum\ProjectStatus;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на обновление проекта. */
final readonly class UpdateProjectRequestDto
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true, message: 'Название проекта не может быть пустым.')]
        #[Assert\Length(max: 255, maxMessage: 'Название проекта не должно превышать 255 символов.')]
        public ?string $name = null,
        #[Assert\Choice(callback: [ProjectStatus::class, 'values'], message: 'Недопустимое значение статуса проекта.')]
        public ?string $status = null,
        #[Assert\Length(max: 1000, maxMessage: 'Описание не должно превышать 1000 символов.')]
        public ?string $description = null,
    ) {}
}
