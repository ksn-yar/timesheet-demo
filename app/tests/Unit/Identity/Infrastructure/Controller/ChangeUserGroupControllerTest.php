<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\ChangeUserGroupUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Domain\Exception\UserNotFoundException;
use App\Identity\Infrastructure\Controller\ChangeUserGroupController;
use App\Identity\Infrastructure\Dto\ChangeUserGroupRequestDto;
use App\Identity\Infrastructure\Transformer\ChangeUserGroupInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера изменения группы пользователя.
 * Проверяет маппинг исключений на HTTP-коды и формат ответа.
 *
 * @internal
 *
 * @coversNothing
 */
class ChangeUserGroupControllerTest extends TestCase
{
    private const string USER_ID = '550e8400-e29b-41d4-a716-446655440001';
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $useCase = $this->createMock(ChangeUserGroupUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $controller = new ChangeUserGroupController($useCase, new ChangeUserGroupInputTransformer());

        $response = $controller(self::USER_ID, new ChangeUserGroupRequestDto(groupId: self::GROUP_ID));

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    #[Test]
    public function returnsNotFoundWhenUserNotFound(): void
    {
        $useCase = $this->createStub(ChangeUserGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new UserNotFoundException(self::USER_ID));

        $controller = new ChangeUserGroupController($useCase, new ChangeUserGroupInputTransformer());

        $response = $controller(self::USER_ID, new ChangeUserGroupRequestDto(groupId: self::GROUP_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsNotFoundWhenGroupNotFound(): void
    {
        $useCase = $this->createStub(ChangeUserGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new GroupNotFoundException(self::GROUP_ID));

        $controller = new ChangeUserGroupController($useCase, new ChangeUserGroupInputTransformer());

        $response = $controller(self::USER_ID, new ChangeUserGroupRequestDto(groupId: self::GROUP_ID));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenEntityDeleted(): void
    {
        $useCase = $this->createStub(ChangeUserGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new EntityDeletedException());

        $controller = new ChangeUserGroupController($useCase, new ChangeUserGroupInputTransformer());

        $response = $controller(self::USER_ID, new ChangeUserGroupRequestDto(groupId: self::GROUP_ID));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }
}
