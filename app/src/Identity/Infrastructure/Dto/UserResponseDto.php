<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Dto;

use OpenApi\Attributes as OA;

/** DTO ответа API с данными пользователя. */
#[OA\Schema(
    schema: 'UserResponse',
    description: 'Данные пользователя.',
)]
final readonly class UserResponseDto
{
    public function __construct(
        #[OA\Property(title: 'Идентификатор', description: 'Уникальный идентификатор пользователя.', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000')]
        public string $id,
        #[OA\Property(title: 'Имя', description: 'Имя пользователя.', example: 'Иван Иванов')]
        public string $name,
        #[OA\Property(title: 'Email', description: 'Электронная почта пользователя.', example: 'ivan@example.com')]
        public string $email,
        #[OA\Property(title: 'Системная роль', description: 'Код системной роли.', example: 'employee')]
        public string $systemRole,
        #[OA\Property(title: 'Метка роли', description: 'Человекочитаемое название системной роли.', example: 'Сотрудник')]
        public string $systemRoleLabel,
        #[OA\Property(title: 'ID группы', description: 'Идентификатор группы пользователя.', format: 'uuid', nullable: true)]
        public ?string $groupId,
        #[OA\Property(title: 'Название группы', description: 'Название группы пользователя.', nullable: true)]
        public ?string $groupName,
        #[OA\Property(title: 'ID роли', description: 'Идентификатор роли пользователя из WorkCatalog.', format: 'uuid', nullable: true)]
        public ?string $roleId,
        #[OA\Property(title: 'Название роли', description: 'Название роли пользователя из WorkCatalog.', nullable: true)]
        public ?string $roleName,
        #[OA\Property(title: 'Активен', description: 'Признак активности пользователя.', example: true)]
        public bool $isActive,
    ) {}
}
