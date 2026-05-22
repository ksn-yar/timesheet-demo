<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\CreateGroupUseCaseInterface;
use App\Identity\Domain\Exception\DuplicateGroupNameException;
use App\Identity\Infrastructure\Controller\CreateGroupController;
use App\Identity\Infrastructure\Dto\CreateGroupRequestDto;
use App\Identity\Infrastructure\Transformer\CreateGroupInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера создания группы.
 * Проверяет HTTP-статусы и обработку исключений дублирования.
 *
 * @internal
 *
 * @coversNothing
 */
class CreateGroupControllerTest extends TestCase
{
    #[Test]
    public function returnsCreatedOnSuccess(): void
    {
        $useCase = $this->createMock(CreateGroupUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $controller = new CreateGroupController($useCase, new CreateGroupInputTransformer());

        $response = $controller(new CreateGroupRequestDto(name: 'Разработка'));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
    }

    #[Test]
    public function returnsUnprocessableWhenDuplicateName(): void
    {
        $useCase = $this->createStub(CreateGroupUseCaseInterface::class);
        $useCase->method('execute')->willThrowException(new DuplicateGroupNameException('Разработка'));

        $controller = new CreateGroupController($useCase, new CreateGroupInputTransformer());

        $response = $controller(new CreateGroupRequestDto(name: 'Разработка'));

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $body = json_decode((string) $response->getContent(), true);
        self::assertArrayHasKey('error', $body);
    }
}
