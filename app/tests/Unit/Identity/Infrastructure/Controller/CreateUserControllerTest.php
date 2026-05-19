<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\CreateUserUseCaseInterface;
use App\Identity\Domain\Exception\DuplicateEmailException;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Infrastructure\Controller\CreateUserController;
use App\Identity\Infrastructure\Dto\CreateUserRequestDto;
use App\Identity\Infrastructure\Transformer\CreateUserInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера создания пользователя.
 * Проверяет маппинг трёх типов исключений на статус 422.
 */
class CreateUserControllerTest extends TestCase
{
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    private function buildRequestDto(): CreateUserRequestDto
    {
        return new CreateUserRequestDto(
            name: 'Иван Иванов',
            email: 'ivan@example.com',
            password: 'secret123',
            systemRole: 'employee',
            groupId: self::GROUP_ID,
        );
    }

    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $useCase = $this->createMock(CreateUserUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $controller = new CreateUserController($useCase, new CreateUserInputTransformer());

        $response = $controller($this->buildRequestDto());

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    #[Test]
    public function returnsUnprocessableWhenDuplicateEmail(): void
    {
        $useCase = $this->createStub(CreateUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new DuplicateEmailException('ivan@example.com'));

        $controller = new CreateUserController($useCase, new CreateUserInputTransformer());

        $response = $controller($this->buildRequestDto());

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenGroupNotFound(): void
    {
        $useCase = $this->createStub(CreateUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new GroupNotFoundException(self::GROUP_ID));

        $controller = new CreateUserController($useCase, new CreateUserInputTransformer());

        $response = $controller($this->buildRequestDto());

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenGroupDeleted(): void
    {
        $useCase = $this->createStub(CreateUserUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new EntityDeletedException());

        $controller = new CreateUserController($useCase, new CreateUserInputTransformer());

        $response = $controller($this->buildRequestDto());

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }
}
