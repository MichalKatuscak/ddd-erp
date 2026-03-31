<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\CreateRole\CreateRoleCommand;
use Identity\Role\Domain\RoleId;
use Identity\Role\Infrastructure\Http\Request\CreateRoleRequest;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles/commands/create-role', methods: ['POST'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class CreateRoleController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(#[MapRequestPayload] CreateRoleRequest $request): JsonResponse
    {
        $roleId = RoleId::generate()->value();

        $this->commandBus->dispatch(new CreateRoleCommand(
            roleId: $roleId,
            name: $request->name,
            permissions: $request->permissions ?? [],
        ));

        return new JsonResponse(['id' => $roleId], Response::HTTP_CREATED);
    }
}
