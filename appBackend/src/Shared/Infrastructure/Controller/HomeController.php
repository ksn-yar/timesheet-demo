<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Controller;

use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/** Контроллер главной страницы. */
#[OA\Tag(name: 'Shared')]
#[OA\Get(
    path: '/api/',
    description: 'Проверочный эндпоинт.',
    summary: 'Главная страница',
)]
#[OA\Response(
    response: Response::HTTP_OK,
    description: 'Приветственное сообщение.',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'message', type: 'string', example: 'Hello world!'),
        ],
        type: 'object',
    ),
)]
#[Route('/api/', name: 'shared_home', methods: ['GET'])]
final class HomeController extends AbstractController
{
    public function __invoke(): JsonResponse
    {
        return $this->json(['message' => 'Hello world!']);
    }
}
