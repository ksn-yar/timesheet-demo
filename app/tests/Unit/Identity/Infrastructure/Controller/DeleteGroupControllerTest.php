<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\DeleteGroupUseCaseInterface;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupHasActiveUsersException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Infrastructure\Controller\DeleteGroupController;
use App\Identity\Infrastructure\Transformer\DeleteGroupInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера удаления группы.
 * Проверяет фиксированный текст ошибки 404 и обработку 422-исключений.
 *
 * @internal
 *
 * @coversNothing
 */
class DeleteGroupControllerTest extends TestCase
{
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $useCase = $this->createMock(DeleteGroupUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $controller = new DeleteGroupController($useCase, new DeleteGroupInputTransformer());

        $response = $controller(self::GROUP_ID);

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    #[Test]
    public function returnsNotFoundWhenGroupNotFound(): void
    {
        $useCase = $this->createStub(DeleteGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new GroupNotFoundException(self::GROUP_ID));

        $controller = new DeleteGroupController($useCase, new DeleteGroupInputTransformer());

        $response = $controller(self::GROUP_ID);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('Группа не найдена.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenEntityDeleted(): void
    {
        $useCase = $this->createStub(DeleteGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new EntityDeletedException());

        $controller = new DeleteGroupController($useCase, new DeleteGroupInputTransformer());

        $response = $controller(self::GROUP_ID);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenGroupHasActiveUsers(): void
    {
        $useCase = $this->createStub(DeleteGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new GroupHasActiveUsersException(self::GROUP_ID));

        $controller = new DeleteGroupController($useCase, new DeleteGroupInputTransformer());

        $response = $controller(self::GROUP_ID);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }
}
