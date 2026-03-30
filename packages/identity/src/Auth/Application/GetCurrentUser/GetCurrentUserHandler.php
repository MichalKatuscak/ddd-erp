<?php
declare(strict_types=1);

namespace Identity\Auth\Application\GetCurrentUser;

use Identity\Role\Domain\RoleRepository;
use Identity\User\Domain\UserId;
use Identity\User\Domain\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetCurrentUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
    ) {}

    public function __invoke(GetCurrentUserQuery $query): CurrentUserDTO
    {
        $user = $this->userRepository->get(UserId::fromString($query->userId));

        $permissions = [];
        foreach ($user->roleIds() as $roleId) {
            try {
                $role = $this->roleRepository->get($roleId);
                $permissions = array_merge($permissions, $role->permissions());
            } catch (\DomainException) {
                // Skip deleted roles
            }
        }
        $permissions = array_values(array_unique($permissions));

        return new CurrentUserDTO(
            id: $user->id()->value(),
            email: $user->email()->value(),
            firstName: $user->name()->firstName(),
            lastName: $user->name()->lastName(),
            permissions: $permissions,
        );
    }
}
