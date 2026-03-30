<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Security;

enum IdentityPermission: string
{
    case MANAGE_USERS = 'identity.users.manage';
    case MANAGE_ROLES = 'identity.roles.manage';
    case VIEW_USERS   = 'identity.users.view';
}
