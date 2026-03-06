<?php

namespace App\DataFixtures;

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
        $user1 = new \App\Entity\Administrator();
        $user1->setEmail('admin@worktogether.com');
        $user1->setPassword($this->hasher->hashPassword($user1,'password'));
        $user1->setRoles(['ROLE_ADMIN']);
        $user1->setPseudo('admin');
        $manager->persist($user1);

        $user2 = new \App\Entity\Client();
        $user2->setEmail('client@worktogether.com');
        $user2->setPassword($this->hasher->hashPassword($user2,'password'));
        $user2->setRoles(['ROLE_CLIENT']);
        $user2->setIsBusiness(false);
        $user2->setPseudo('client');
        $manager->persist($user2);

        $manager->flush();
    }
}
