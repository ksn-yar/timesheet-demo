<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\UpdateUserUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Infrastructure\Controller\UpdateUserController;
use App\Identity\Infrastructure\Dto\UpdateUserRequestDto;
use App\Identity\Infrastructure\Transformer\UpdateUserInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера обновления пользователя.
 * Проверяет фиксированный текст ошибки 404 и обработку EntityDeletedException.
 */
class UpdateUserControllerTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $useCase = $this->createMock(UpdateUserUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $controller = new UpdateUserController($useCase, new UpdateUserInputTransformer());

        $response = $controller(self::USER_ID, new UpdateUserRequestDto(name: 'Новое имя', systemRole: 'employee'));

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    #[Test]
    public function returnsNotFoundWhenUserNotFound(): void
    {
        $useCase = $this->createStub(UpdateUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new UserNotFoundException(self::USER_ID));

        $controller = new UpdateUserController($useCase, new UpdateUserInputTransformer());

        $response = $controller(self::USER_ID, new UpdateUserRequestDto(name: 'Новое имя', systemRole: 'employee'));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('Пользователь не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenEntityDeleted(): void
    {
        $useCase = $this->createStub(UpdateUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new EntityDeletedException());

        $controller = new UpdateUserController($useCase, new UpdateUserInputTransformer());

        $response = $controller(self::USER_ID, new UpdateUserRequestDto(name: 'Новое имя', systemRole: 'employee'));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }
}
