<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\User;
use App\Entity\ApiToken;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher
    )
    {
    }


    public function load(ObjectManager $manager): void
    {
        // Create root user
        $rootUser = new User();
        $rootUser->setEmail('root@test.com');
        $rootUser->setPassword(
            $this->userPasswordHasher->hashPassword(
                $rootUser,
                '12345678'
            )
        );
        $rootUser->setPhone('+0000000');
        $rootUser->setRoles(['ROLE_ROOT']);
        $manager->persist($rootUser);

        // Create API token for root user
        $rootToken = new ApiToken();
        $rootToken->setUser($rootUser);
        $rootToken->setToken('root-token-for-testing-purposes-1234567890abcdef12345678');
        $manager->persist($rootToken);

        // Create regular user 1
        $user1 = new User();
        $user1->setEmail('test@test.com');
        $user1->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user1,
                '12345678'
            )
        );
        $user1->setPhone('+1111111');
        $manager->persist($user1);

        // Create API token for user 1
        $token1 = new ApiToken();
        $token1->setUser($user1);
        $token1->setToken('user1-token-for-testing-purposes-1234567890abcdef1234567');
        $manager->persist($token1);

        // Create regular user 2
        $user2 = new User();
        $user2->setEmail('test2@test2.com');
        $user2->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user2,
                '12345678'
            )
        );
        $user2->setPhone('+9999999');
        $manager->persist($user2);

        // Create API token for user 2
        $token2 = new ApiToken();
        $token2->setUser($user2);
        $token2->setToken('user2-token-for-testing-purposes-1234567890abcdef1234567');
        $manager->persist($token2);

        $manager->flush();
    }
}
