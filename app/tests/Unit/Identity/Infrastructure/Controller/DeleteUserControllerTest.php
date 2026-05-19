<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\DeleteUserUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\UserHasLinkedTicketsException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Infrastructure\Controller\DeleteUserController;
use App\Identity\Infrastructure\Transformer\DeleteUserInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера удаления пользователя.
 * Проверяет фиксированный текст ошибки 404 и обработку 422-исключений.
 */
class DeleteUserControllerTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $useCase = $this->createMock(DeleteUserUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $controller = new DeleteUserController($useCase, new DeleteUserInputTransformer());

        $response = $controller(self::USER_ID);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    #[Test]
    public function returnsNotFoundWhenUserNotFound(): void
    {
        $useCase = $this->createStub(DeleteUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new UserNotFoundException(self::USER_ID));

        $controller = new DeleteUserController($useCase, new DeleteUserInputTransformer());

        $response = $controller(self::USER_ID);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('Пользователь не найден.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenEntityDeleted(): void
    {
        $useCase = $this->createStub(DeleteUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new EntityDeletedException());

        $controller = new DeleteUserController($useCase, new DeleteUserInputTransformer());

        $response = $controller(self::USER_ID);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenUserHasLinkedTickets(): void
    {
        $useCase = $this->createStub(DeleteUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new UserHasLinkedTicketsException(self::USER_ID));

        $controller = new DeleteUserController($useCase, new DeleteUserInputTransformer());

        $response = $controller(self::USER_ID);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }
}
