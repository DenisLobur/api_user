<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\User;
use App\Entity\MicroPost;
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

        $manager->flush();
    }
}
