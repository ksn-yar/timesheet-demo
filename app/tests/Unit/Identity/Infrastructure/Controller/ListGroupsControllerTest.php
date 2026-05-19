<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Controller;

use App\Identity\Application\Port\ListGroupsUseCaseInterface;
use App\Identity\Infrastructure\Controller\ListGroupsController;
use App\Identity\Infrastructure\Dto\GroupListResponseDto;
use App\Identity\Infrastructure\Dto\ListGroupsRequestDto;
use App\Identity\Infrastructure\Presenter\ListGroupsPresenterInterface;
use App\Identity\Infrastructure\Transformer\ListGroupsInputTransformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Тесты контроллера получения списка групп.
 * Требует мок-контейнера, так как контроллер вызывает $this->json() из AbstractController.
 */
class ListGroupsControllerTest extends TestCase
{
    private function buildController(
        ListGroupsUseCaseInterface $useCase,
        ListGroupsPresenterInterface $presenter,
    ): ListGroupsController {
        $controller = new ListGroupsController($useCase, new ListGroupsInputTransformer(), $presenter);

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
        $useCase = $this->createStub(ListGroupsUseCaseInterface::class);
        $presenter = $this->createStub(ListGroupsPresenterInterface::class);
        $presenter->method('getResponseDto')->willReturn(new GroupListResponseDto([], 0, 1, 20));

        $controller = $this->buildController($useCase, $presenter);

        $response = $controller(new ListGroupsRequestDto());

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    #[Test]
    public function callsUseCaseAndPresenter(): void
    {
        $useCase = $this->createMock(ListGroupsUseCaseInterface::class);
        $useCase->expects($this->once())->method('execute');

        $presenter = $this->createMock(ListGroupsPresenterInterface::class);
        $presenter->expects($this->once())
            ->method('getResponseDto')
            ->willReturn(new GroupListResponseDto([], 0, 1, 20));

        $controller = $this->buildController($useCase, $presenter);

        $controller(new ListGroupsRequestDto());
    }
}
