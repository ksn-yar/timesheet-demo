<?php

declare(strict_types=1);

namespace App\ProjectManagement\Infrastructure\Dto;

use App\ProjectManagement\Infrastructure\Validator\ExactlyOneParent;
use Symfony\Component\Validator\Constraints as Assert;

/** DTO входящего HTTP-запроса на создание задачи. */
#[ExactlyOneParent]
final readonly class CreateTaskRequestDto
{
    public function __construct(
        #[Assert\Uuid(message: 'Идентификатор проекта должен быть валидным UUID.')]
        public ?string $projectId = null,
        #[Assert\Uuid(message: 'Идентификатор запроса на изменение должен быть валидным UUID.')]
        public ?string $crId = null,
        #[Assert\NotBlank(message: 'Название задачи обязательно.')]
        #[Assert\Length(max: 255, maxMessage: 'Название задачи не должно превышать 255 символов.')]
        public string $name = '',
        #[Assert\Length(max: 1000, maxMessage: 'Описание не должно превышать 1000 символов.')]
        public ?string $description = null,
        #[Assert\Positive(message: 'Оценка трудозатрат должна быть положительным числом.')]
        public ?float $estimate = null,
    ) {}
}
