<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\ListUsersUseCaseInterface;
use App\Identity\Infrastructure\Controller\ListUsersController;
use App\Identity\Infrastructure\Dto\ListUsersRequestDto;
use App\Identity\Infrastructure\Dto\UserListResponseDto;
use App\Identity\Infrastructure\Presenter\ListUsersPresenterInterface;
use App\Identity\Infrastructure\Transformer\ListUsersInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера получения списка пользователей.
 * Требует мок-контейнера, так как контроллер вызывает $this->json() из AbstractController.
 */
class ListUsersControllerTest extends TestCase
{
    private function buildController(
        ListUsersUseCaseInterface $useCase,
        ListUsersPresenterInterface $presenter,
    ): ListUsersController {
        $controller = new ListUsersController($useCase, new ListUsersInputTransformer(), $presenter);

        // json() из AbstractController обращается к контейнеру.
        // Мок с has()=false вынуждает метод вернуть new JsonResponse($data) напрямую.
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $controller->setContainer($container);

        return $controller;
    }

    #[Test]
    public function returnsOkWithPresenterResponse(): void
    {
        $useCase = $this->createStub(ListUsersUseCaseInterface::class);
        $presenter = $this->createStub(ListUsersPresenterInterface::class);
        $presenter->method('getResponseDto')->willReturn(new UserListResponseDto([], 0, 1, 20));

        $controller = $this->buildController($useCase, $presenter);

        $response = $controller(new ListUsersRequestDto());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function callsUseCaseAndPresenter(): void
    {
        $useCase = $this->createMock(ListUsersUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $presenter = $this->createMock(ListUsersPresenterInterface::class);
        $presenter->expects($this->once())
            ->method('getResponseDto')
            ->willReturn(new UserListResponseDto([], 0, 1, 20));

        $controller = $this->buildController($useCase, $presenter);

        $controller(new ListUsersRequestDto());
    }
}
