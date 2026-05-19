<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\UpdateGroupUseCaseInterface;
use App\Identity\Domain\Exception\DuplicateGroupNameException;
use App\Identity\Domain\Exception\EntityDeletedException;
use App\Identity\Domain\Exception\GroupNotFoundException;
use App\Identity\Infrastructure\Controller\UpdateGroupController;
use App\Identity\Infrastructure\Dto\UpdateGroupRequestDto;
use App\Identity\Infrastructure\Transformer\UpdateGroupInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера обновления группы.
 * Проверяет фиксированный текст ошибки 404 и два вида 422-исключений.
 */
class UpdateGroupControllerTest extends TestCase
{
    private const string GROUP_ID = '550e8400-e29b-41d4-a716-446655440002';

    #[Test]
    public function returnsNoContentOnSuccess(): void
    {
        $useCase = $this->createMock(UpdateGroupUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $controller = new UpdateGroupController($useCase, new UpdateGroupInputTransformer());

        $response = $controller(self::GROUP_ID, new UpdateGroupRequestDto(name: 'Новое название'));

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    #[Test]
    public function returnsNotFoundWhenGroupNotFound(): void
    {
        $useCase = $this->createStub(UpdateGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new GroupNotFoundException(self::GROUP_ID));

        $controller = new UpdateGroupController($useCase, new UpdateGroupInputTransformer());

        $response = $controller(self::GROUP_ID, new UpdateGroupRequestDto(name: 'Новое название'));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertSame('Группа не найдена.', $body['error']);
    }

    #[Test]
    public function returnsUnprocessableWhenDuplicateName(): void
    {
        $useCase = $this->createStub(UpdateGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new DuplicateGroupNameException('Новое название'));

        $controller = new UpdateGroupController($useCase, new UpdateGroupInputTransformer());

        $response = $controller(self::GROUP_ID, new UpdateGroupRequestDto(name: 'Новое название'));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }

    #[Test]
    public function returnsUnprocessableWhenEntityDeleted(): void
    {
        $useCase = $this->createStub(UpdateGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new EntityDeletedException());

        $controller = new UpdateGroupController($useCase, new UpdateGroupInputTransformer());

        $response = $controller(self::GROUP_ID, new UpdateGroupRequestDto(name: 'Новое название'));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }
}
