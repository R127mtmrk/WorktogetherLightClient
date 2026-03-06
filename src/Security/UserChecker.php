<?php

namespace App\Security;

use App\Entity\Client;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // Only allow instances of Client to authenticate here
        if (!$user instanceof Client && !$user instanceof \App\Entity\Administrator) {
            // This message will be shown to the user on failed authentication
            throw new CustomUserMessageAccountStatusException('Seuls les clients peuvent se connecter ici.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // no additional checks after authentication for now
    }
}
