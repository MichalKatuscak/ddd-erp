<?php
declare(strict_types=1);
namespace Sales\Inquiry\Infrastructure\Storage;
interface FileStorage
{
    public function store(string $tmpPath, string $originalName, string $mimeType): string;
    public function absolutePath(string $storedPath): string;
}
