<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Storage;
final class LocalFileStorage implements FileStorage
{
    public function __construct(private readonly string $uploadDir) {}

    public function store(string $tmpPath, string $originalName, string $mimeType): string
    {
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . ($ext !== '' ? '.' . $ext : '');
        $destination = $this->uploadDir . '/' . $filename;
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        copy($tmpPath, $destination);
        return $filename;
    }

    public function absolutePath(string $storedPath): string
    {
        return $this->uploadDir . '/' . $storedPath;
    }
}
