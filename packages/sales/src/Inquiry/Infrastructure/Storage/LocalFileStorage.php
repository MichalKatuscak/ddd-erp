<?php
declare(strict_types=1);

namespace Sales\Inquiry\Infrastructure\Storage;

use Symfony\Component\Uid\Uuid;

final class LocalFileStorage implements FileStorage
{
    private const ALLOWED_MIMES = ['application/pdf', 'image/png', 'image/jpeg', 'image/webp'];

    public function __construct(private readonly string $uploadDir) {}

    public function store(string $tmpPath, string $originalName, string $mimeType): string
    {
        if (!in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException("Unsupported MIME type: $mimeType");
        }
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        $ext      = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = Uuid::v7() . ($ext ? '.' . $ext : '');
        $dest     = $this->uploadDir . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $dest) && !rename($tmpPath, $dest)) {
            throw new \RuntimeException("Could not store file at $dest");
        }
        return $filename;
    }

    public function absolutePath(string $storedPath): string
    {
        return $this->uploadDir . '/' . $storedPath;
    }
}
