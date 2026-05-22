<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Persistence\Entity\ChangeRequest as ChangeRequestOrmEntity;
use App\Persistence\Entity\Client as ClientOrmEntity;
use App\Persistence\Entity\Group as GroupOrmEntity;
use App\Persistence\Entity\Project as ProjectOrmEntity;
use App\Persistence\Entity\Rate as RateOrmEntity;
use App\Persistence\Entity\Role as RoleOrmEntity;
use App\Persistence\Entity\Task as TaskOrmEntity;
use App\Persistence\Entity\User as UserOrmEntity;
use App\Persistence\Entity\Work as WorkOrmEntity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Базовый класс для функциональных тестов, работающих с реальным HTTP-стеком.
 * Изоляция данных обеспечивается через DAMA\DoctrineTestBundle: каждый тест выполняется
 * в транзакции, которая автоматически откатывается после завершения.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    /** Email тестового пользователя-администратора для генерации JWT. */
    protected const string AUTH_USER_EMAIL = 'test-admin@example.com';
    protected const string AUTH_USER_PASSWORD = 'test-secret-123';

    /** UUID тестового admin-пользователя — фиксированный для воспроизводимости. */
    protected const string AUTH_USER_ID = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

    /** Зарезервированные UUID для тестовых фикстур — валидные v4. */
    protected const string FIXTURE_USER_ID_1 = 'b1234567-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_USER_ID_2 = 'b1234567-89ab-4cde-8f01-234567890002';
    protected const string FIXTURE_USER_ID_3 = 'b1234567-89ab-4cde-8f01-234567890003';
    protected const string FIXTURE_USER_ID_4 = 'b1234567-89ab-4cde-8f01-234567890004';
    protected const string FIXTURE_USER_ID_5 = 'b1234567-89ab-4cde-8f01-234567890050';

    protected const string FIXTURE_GROUP_ID_1 = 'c1234567-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_GROUP_ID_2 = 'c1234567-89ab-4cde-8f01-234567890002';
    protected const string FIXTURE_GROUP_ID_3 = 'c1234567-89ab-4cde-8f01-234567890003';
    protected const string FIXTURE_GROUP_ID_4 = 'c1234567-89ab-4cde-8f01-234567890010';

    protected const string FIXTURE_CLIENT_ID_1 = 'd1234567-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_CLIENT_ID_2 = 'd1234567-89ab-4cde-8f01-234567890002';

    protected const string FIXTURE_PROJECT_ID_1 = 'e1234567-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_PROJECT_ID_2 = 'e1234567-89ab-4cde-8f01-234567890002';

    protected const string FIXTURE_CR_ID_1 = 'a2345678-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_CR_ID_2 = 'a2345678-89ab-4cde-8f01-234567890002';

    protected const string FIXTURE_TASK_ID_1 = 'a3456789-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_TASK_ID_2 = 'a3456789-89ab-4cde-8f01-234567890002';

    protected const string FIXTURE_WORK_ID_1 = 'f1234567-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_WORK_ID_2 = 'f1234567-89ab-4cde-8f01-234567890002';

    protected const string FIXTURE_ROLE_ID_1 = 'f2345678-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_ROLE_ID_2 = 'f2345678-89ab-4cde-8f01-234567890002';

    protected const string FIXTURE_RATE_ID_1 = 'f3456789-89ab-4cde-8f01-234567890001';
    protected const string FIXTURE_RATE_ID_2 = 'f3456789-89ab-4cde-8f01-234567890002';
    protected KernelBrowser $client;
    protected string $jwtToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->jwtToken = $this->createAuthUserAndGenerateToken();
    }

    /**
     * Выполняет аутентифицированный HTTP-запрос с JWT-заголовком.
     *
     * @param array<string, mixed> $body
     */
    protected function authenticatedRequest(
        string $method,
        string $url,
        array $body = [],
    ): void {
        $content = [] !== $body ? json_encode($body, \JSON_THROW_ON_ERROR) : null;

        $this->client->request(
            method: $method,
            uri: $url,
            server: $this->getAuthHeaders(),
            content: $content,
        );
    }

    /**
     * Возвращает массив server-параметров для KernelBrowser с JWT-токеном.
     * Ключи должны быть в формате HTTP_* для корректной передачи заголовков.
     *
     * @return array<string, string>
     */
    protected function getAuthHeaders(): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ];
    }

    /**
     * Декодирует JSON из последнего ответа.
     *
     * @return array<string, mixed>
     */
    protected function getResponseJson(): array
    {
        $content = $this->client->getResponse()->getContent();
        \assert(false !== $content);

        return json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get(EntityManagerInterface::class);

        return $em;
    }

    /**
     * Создаёт группу в БД и возвращает её ID.
     * Вспомогательный метод для подготовки данных в тестах.
     */
    protected function createGroup(
        string $name = 'Тестовая группа',
        string $id = self::FIXTURE_GROUP_ID_1,
    ): string {
        $em = $this->getEntityManager();

        $group = new GroupOrmEntity();
        $group->setId($id);
        $group->setName($name);
        $group->setCreatedAt(new DateTimeImmutable());

        $em->persist($group);
        $em->flush();

        return $id;
    }

    /**
     * Создаёт пользователя в БД и возвращает его ID.
     * Вспомогательный метод для подготовки данных в тестах.
     */
    protected function createUser(
        string $email,
        string $name = 'Иван Иванов',
        string $systemRole = 'employee',
        string $id = self::FIXTURE_USER_ID_1,
    ): string {
        $em = $this->getEntityManager();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $user = new UserOrmEntity();
        $user->setId($id);
        $user->setName($name);
        $user->setEmail($email);
        $user->setPasswordHash($hasher->hashPassword($user, 'secret123'));
        $user->setSystemRole($systemRole);
        $user->setIsActive(true);
        $user->setCreatedAt(new DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        return $id;
    }

    /** Создаёт клиента в БД и возвращает его ID. */
    protected function createClientEntity(
        string $name = 'Тестовый клиент',
        string $id = self::FIXTURE_CLIENT_ID_1,
    ): string {
        $em = $this->getEntityManager();

        $client = new ClientOrmEntity();
        $client->setId($id);
        $client->setName($name);
        $client->setCreatedAt(new DateTimeImmutable());

        $em->persist($client);
        $em->flush();

        return $id;
    }

    /** Создаёт проект в БД и возвращает его ID. */
    protected function createProject(
        string $clientId,
        string $name = 'Тестовый проект',
        string $status = 'active',
        string $id = self::FIXTURE_PROJECT_ID_1,
    ): string {
        $em = $this->getEntityManager();

        $client = $em->find(ClientOrmEntity::class, $clientId);
        \assert(null !== $client);

        $project = new ProjectOrmEntity();
        $project->setId($id);
        $project->setClient($client);
        $project->setName($name);
        $project->setStatus($status);
        $project->setCreatedAt(new DateTimeImmutable());

        $em->persist($project);
        $em->flush();

        return $id;
    }

    /** Создаёт запрос на изменение в БД и возвращает его ID. */
    protected function createChangeRequest(
        string $projectId,
        string $name = 'Тестовый CR',
        string $id = self::FIXTURE_CR_ID_1,
    ): string {
        $em = $this->getEntityManager();

        $project = $em->find(ProjectOrmEntity::class, $projectId);
        \assert(null !== $project);

        $cr = new ChangeRequestOrmEntity();
        $cr->setId($id);
        $cr->setProject($project);
        $cr->setName($name);
        $cr->setCreatedAt(new DateTimeImmutable());

        $em->persist($cr);
        $em->flush();

        return $id;
    }

    /** Создаёт задачу в БД и возвращает её ID. Ровно один из projectId/crId должен быть задан. */
    protected function createTask(
        string $name = 'Тестовая задача',
        ?string $projectId = null,
        ?string $crId = null,
        string $id = self::FIXTURE_TASK_ID_1,
    ): string {
        $em = $this->getEntityManager();

        $task = new TaskOrmEntity();
        $task->setId($id);
        $task->setName($name);
        $task->setCreatedAt(new DateTimeImmutable());

        if (null !== $projectId) {
            $project = $em->find(ProjectOrmEntity::class, $projectId);
            \assert(null !== $project);
            $task->setProject($project);
        }

        if (null !== $crId) {
            $cr = $em->find(ChangeRequestOrmEntity::class, $crId);
            \assert(null !== $cr);
            $task->setChangeRequest($cr);
        }

        $em->persist($task);
        $em->flush();

        return $id;
    }

    /** Создаёт вид работ в БД и возвращает его ID. */
    protected function createWork(
        string $name = 'Тестовый вид работ',
        string $id = self::FIXTURE_WORK_ID_1,
    ): string {
        $em = $this->getEntityManager();

        $work = new WorkOrmEntity();
        $work->setId($id);
        $work->setName($name);
        $work->setCreatedAt(new DateTimeImmutable());

        $em->persist($work);
        $em->flush();

        return $id;
    }

    /** Создаёт роль в БД и возвращает её ID. */
    protected function createRole(
        string $name = 'Тестовая роль',
        string $id = self::FIXTURE_ROLE_ID_1,
    ): string {
        $em = $this->getEntityManager();

        $role = new RoleOrmEntity();
        $role->setId($id);
        $role->setName($name);
        $role->setCreatedAt(new DateTimeImmutable());

        $em->persist($role);
        $em->flush();

        return $id;
    }

    /** Создаёт ставку в БД и возвращает её ID. */
    protected function createRate(
        string $amount = '1500.00',
        string $currency = 'RUB',
        string $effectiveFrom = '2026-01-01',
        ?string $roleId = null,
        ?string $workId = null,
        string $id = self::FIXTURE_RATE_ID_1,
    ): string {
        $em = $this->getEntityManager();

        $rate = new RateOrmEntity();
        $rate->setId($id);
        $rate->setAmount($amount);
        $rate->setCurrency($currency);
        $rate->setEffectiveFrom(new DateTimeImmutable($effectiveFrom));
        $rate->setCreatedAt(new DateTimeImmutable());

        if (null !== $roleId) {
            $role = $em->find(RoleOrmEntity::class, $roleId);
            \assert(null !== $role);
            $rate->setRole($role);
        }

        if (null !== $workId) {
            $work = $em->find(WorkOrmEntity::class, $workId);
            \assert(null !== $work);
            $rate->setWork($work);
        }

        $em->persist($rate);
        $em->flush();

        return $id;
    }

    /**
     * Создаёт тестового пользователя-администратора в БД и возвращает JWT.
     * Пользователь используется только для аутентификации в тестах.
     */
    private function createAuthUserAndGenerateToken(): string
    {
        $em = $this->getEntityManager();

        /** @var UserPasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        $user = new UserOrmEntity();
        $user->setId(self::AUTH_USER_ID);
        $user->setName('Test Admin');
        $user->setEmail(self::AUTH_USER_EMAIL);
        $user->setPasswordHash($hasher->hashPassword($user, self::AUTH_USER_PASSWORD));
        $user->setSystemRole('admin');
        $user->setIsActive(true);
        $user->setCreatedAt(new DateTimeImmutable());

        $em->persist($user);
        $em->flush();

        return $jwtManager->create($user);
    }
}
