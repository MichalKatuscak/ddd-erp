<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Http;
use Sales\Inquiry\Application\AttachFile\AttachFileCommand;
use Sales\Inquiry\Infrastructure\Storage\FileStorage;
use Sales\Infrastructure\Security\SalesPermission;
use SharedKernel\Application\CommandBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/inquiries/{id}/attachments', methods: ['POST'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class AttachFileController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly FileStorage         $fileStorage,
    ) {}
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if ($file === null) {
            return new JsonResponse(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }
        $storedPath = $this->fileStorage->store(
            $file->getRealPath(),
            $file->getClientOriginalName(),
            $file->getMimeType() ?? 'application/octet-stream',
        );
        $this->commandBus->dispatch(new AttachFileCommand(
            $id, $storedPath, $file->getMimeType() ?? '', $file->getClientOriginalName(),
        ));
        return new JsonResponse(['path' => $storedPath], Response::HTTP_CREATED);
    }
}
