<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Persistence\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Фикстура тестового администратора: admin@example.com / admin */
class AdminUserFixture extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@example.com']);

        if (null !== $existing) {
            return;
        }

        $user = new User();
        $user->setId('00000000-0000-4000-8000-000000000001');
        $user->setName('Admin');
        $user->setEmail('admin@example.com');
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, 'admin'));
        $user->setSystemRole('admin');
        $user->setCreatedAt(new DateTimeImmutable());

        $manager->persist($user);
        $manager->flush();
    }
}
