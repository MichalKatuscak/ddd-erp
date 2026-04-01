<?php
declare(strict_types=1);
namespace Sales\Infrastructure\Http;
use Sales\Inquiry\Infrastructure\Storage\FileStorage;
use Sales\Infrastructure\Security\SalesPermission;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
#[Route('/api/sales/attachments/{filename}', methods: ['GET'])]
#[IsGranted(SalesPermission::ManageInquiries->value)]
final class GetAttachmentController extends AbstractController
{
    public function __construct(private readonly FileStorage $fileStorage) {}
    public function __invoke(string $filename): Response
    {
        $absolutePath = $this->fileStorage->absolutePath($filename);
        if (!file_exists($absolutePath)) {
            return new Response(null, Response::HTTP_NOT_FOUND);
        }
        return new BinaryFileResponse($absolutePath);
    }
}
