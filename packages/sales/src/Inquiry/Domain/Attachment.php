<?php
declare(strict_types=1);

namespace Sales\Inquiry\Domain;

use Symfony\Component\Uid\Uuid;

final class Attachment
{
    public readonly string $id;

    public function __construct(
        public readonly string $path,
        public readonly string $mimeType,
        public readonly string $originalName,
        ?string $id = null,
    ) {
        $this->id = $id ?? (string) Uuid::v7();
    }
}
