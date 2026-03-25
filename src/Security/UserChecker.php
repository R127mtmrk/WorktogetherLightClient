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

    public function checkIsConnectable(UserInterface $user): bool
    {
        if ($user instanceof Client) {
            return $user->getPwdErr() < 3; // Allow connection if password error count is less than 3
        }
        return true; // For other user types, allow connection by default
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        // no additional checks after authentication for now
    }
}
