<?php

declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Maps permission strings (e.g. "identity.users.view") to the ROLE_* format
 * that JwtAuthenticator stores in SecurityUser::getRoles().
 */
final class PermissionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return str_contains($attribute, '.');
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $requiredRole = 'ROLE_' . strtoupper(str_replace('.', '_', $attribute));

        return in_array($requiredRole, $token->getRoleNames(), true);
    }
}
