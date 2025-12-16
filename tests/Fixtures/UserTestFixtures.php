<?php

namespace App\Tests\Fixtures;

use App\Entity\User;
use App\Entity\ApiToken;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserTestFixtures extends Fixture
{
    public const ROOT_USER_EMAIL = 'rt@t.com';
    public const ROOT_USER_PASSWORD = '12345678';
    public const ROOT_USER_PHONE = '+0000000';
    public const ROOT_TOKEN = 'root-test-token-1234567890abcdef1234567890abcdef12345678';

    public const USER_EMAIL = 'u1@t.com';
    public const USER_PASSWORD = '12345678';
    public const USER_PHONE = '+1111111';
    public const USER_TOKEN = 'user-test-token-1234567890abcdef1234567890abcdef12345678';

    public const USER2_EMAIL = 'u2@t.com';
    public const USER2_PASSWORD = '87654321';
    public const USER2_PHONE = '+2222222';
    public const USER2_TOKEN = 'user2-test-token-234567890abcdef1234567890abcdef123456789';

    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Create root user
        $rootUser = new User();
        $rootUser->setEmail(self::ROOT_USER_EMAIL);
        $rootUser->setPassword(
            $this->userPasswordHasher->hashPassword($rootUser, self::ROOT_USER_PASSWORD)
        );
        $rootUser->setPhone(self::ROOT_USER_PHONE);
        $rootUser->setRoles(['ROLE_ROOT']);
        $manager->persist($rootUser);

        $rootToken = new ApiToken();
        $rootToken->setUser($rootUser);
        $rootToken->setToken(self::ROOT_TOKEN);
        $manager->persist($rootToken);

        // Create regular user 1
        $user1 = new User();
        $user1->setEmail(self::USER_EMAIL);
        $user1->setPassword(
            $this->userPasswordHasher->hashPassword($user1, self::USER_PASSWORD)
        );
        $user1->setPhone(self::USER_PHONE);
        $manager->persist($user1);

        $token1 = new ApiToken();
        $token1->setUser($user1);
        $token1->setToken(self::USER_TOKEN);
        $manager->persist($token1);

        // Create regular user 2
        $user2 = new User();
        $user2->setEmail(self::USER2_EMAIL);
        $user2->setPassword(
            $this->userPasswordHasher->hashPassword($user2, self::USER2_PASSWORD)
        );
        $user2->setPhone(self::USER2_PHONE);
        $manager->persist($user2);

        $token2 = new ApiToken();
        $token2->setUser($user2);
        $token2->setToken(self::USER2_TOKEN);
        $manager->persist($token2);

        $manager->flush();

        $this->addReference('root-user', $rootUser);
        $this->addReference('user1', $user1);
        $this->addReference('user2', $user2);
    }
}

