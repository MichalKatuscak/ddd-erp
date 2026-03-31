<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class AssignRolesToUserRequest
{
    public function __construct(
        // NotNull only — empty array is valid (removes all roles from user)
        #[Assert\NotNull]
        #[Assert\All([new Assert\Uuid()])]
        public readonly ?array $role_ids = null,
    ) {}
}
