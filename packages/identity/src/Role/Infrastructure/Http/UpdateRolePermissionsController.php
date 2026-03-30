<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http;

use Identity\Auth\Infrastructure\Security\IdentityPermission;
use Identity\Role\Application\UpdateRolePermissions\UpdateRolePermissionsCommand;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/identity/roles/commands/update-role-permissions/{id}', methods: ['PUT'])]
#[IsGranted(IdentityPermission::MANAGE_ROLES->value)]
final class UpdateRolePermissionsController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        $this->commandBus->dispatch(new UpdateRolePermissionsCommand(
            roleId: $id,
            permissions: (array) ($data['permissions'] ?? []),
        ));

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
