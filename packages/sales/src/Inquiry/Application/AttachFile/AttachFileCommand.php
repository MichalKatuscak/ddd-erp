<?php
declare(strict_types=1);
namespace Sales\Inquiry\Application\AttachFile;
final readonly class AttachFileCommand
{
    public function __construct(
        public string $inquiryId,
        public string $storedPath,
        public string $mimeType,
        public string $originalName,
    ) {}
}
