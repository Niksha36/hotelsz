<?php

declare(strict_types=1);

namespace App\Tests\api\TestHelpers;

use App\Entity\UserEntity;
use App\Security\JwtService;
use App\Security\UserRole;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @mixin \Symfony\Bundle\FrameworkBundle\Test\WebTestCase
 * @method static KernelBrowser createClient()
 * @method static ContainerInterface getContainer()
 */
trait AuthenticatedClientTrait
{
    private function createAuthenticatedClient(UserRole $userRole = UserRole::ROLE_USER): KernelBrowser
    {
        $client = static::createClient();
        $container = static::getContainer();

        /** @var ObjectManager $em */
        $em = $container->get('doctrine')->getManager();

        $phone = '+7999' . substr((string) random_int(1000000, 9999999), 0, 7);
        $firstName = 'Test';
        $lastName = 'User';
        $email = 'test.user+' . uniqid() . '@example.test';

        $user = new UserEntity($phone, $firstName, $lastName, $email);

        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $hashed = $passwordHasher->hashPassword($user, 'Aa1!strongPass');

        $user->setPassword($hashed);
        $user->setRoles([$userRole->value]);

        $em->persist($user);
        $em->flush();

        $userId = $user->getId();
        if ($userId === null) {
            throw new RuntimeException('User id is null after flush');
        }

        /** @var JwtService $jwtService */
        $jwtService = $container->get(JwtService::class);
        $accessToken = $jwtService->createAccessToken($userId);

        $client->setServerParameter('HTTP_Authorization', 'Bearer ' . $accessToken);

        return $client;
    }

    private function createRefreshTokenForUser(int $userId): string
    {
        /** @var JwtService $jwtService */
        $jwtService = static::getContainer()->get(JwtService::class);
        return $jwtService->createRefreshToken($userId);
    }
}
