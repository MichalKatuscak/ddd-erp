<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\Auth\Domain\RefreshTokenId;

final class RefreshTokenIdType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RefreshTokenId
    {
        if ($value === null) {
            return null;
        }
        return RefreshTokenId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof RefreshTokenId ? $value->value() : (string) $value;
    }
}
