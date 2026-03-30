<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, null>
 */
final class ContactsVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, array_column(ContactsPermission::cases(), 'value'), true);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if ($user === null) {
            return false;
        }

        // Mapping: permission string → ROLE_* convention
        $role = 'ROLE_' . strtoupper(str_replace('.', '_', $attribute));
        return in_array($role, $token->getRoleNames(), true);
    }
}
