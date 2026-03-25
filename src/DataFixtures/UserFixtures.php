<?php

namespace App\DataFixtures;

use App\Entity\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(UserPasswordHasherInterface $hasher)
    {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $user1 = new Client();
        $user1->setEmail('buisness@worktogether.com');
        $user1->setPassword($this->hasher->hashPassword($user1,'password'));
        $user1->setRoles(['ROLE_CLIENT']);
        $user1->setIsBusiness(true);
        $user1->setPseudo('business');
        $user1->setPwdErr(0);
        $manager->persist($user1);

        $user2 = new Client();
        $user2->setEmail('particular@worktogether.com');
        $user2->setPassword($this->hasher->hashPassword($user2,'password'));
        $user2->setRoles(['ROLE_CLIENT']);
        $user2->setIsBusiness(false);
        $user2->setPseudo('client');
        $user2->setPwdErr(0);
        $manager->persist($user2);

        $manager->flush();
    }
}
