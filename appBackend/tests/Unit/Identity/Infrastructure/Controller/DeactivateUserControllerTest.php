<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\DeactivateUserUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserAlreadyDeactivatedException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Infrastructure\Controller\DeactivateUserController;
use App\Identity\Infrastructure\Transformer\DeactivateUserInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера деактивации пользователя.
 * Проверяет фиксированный текст ошибки 404 и динамические сообщения 422.
 *
 * @internal
 *
 * @coversNothing
 */
class DeactivateUserControllerTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $useCase = $this->createMock(DeactivateUserUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $controller = new DeactivateUserController($useCase, new DeactivateUserInputTransformer());

        $response = $controller(self::USER_ID);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    #[Test]
    public function returnsNotFoundWhenUserNotFound(): void
    {
        $useCase = $this->createStub(DeactivateUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new UserNotFoundException(self::USER_ID));

        $controller = new DeactivateUserController($useCase, new DeactivateUserInputTransformer());

        $response = $controller(self::USER_ID);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('Пользователь не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenEntityDeleted(): void
    {
        $useCase = $this->createStub(DeactivateUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new EntityDeletedException());

        $controller = new DeactivateUserController($useCase, new DeactivateUserInputTransformer());

        $response = $controller(self::USER_ID);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenUserAlreadyDeactivated(): void
    {
        $useCase = $this->createStub(DeactivateUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new UserAlreadyDeactivatedException(self::USER_ID));

        $controller = new DeactivateUserController($useCase, new DeactivateUserInputTransformer());

        $response = $controller(self::USER_ID);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }
}
