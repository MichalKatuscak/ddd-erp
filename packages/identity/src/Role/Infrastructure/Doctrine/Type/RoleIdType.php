<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\Role\Domain\RoleId;

final class RoleIdType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RoleId
    {
        if ($value === null) {
            return null;
        }
        return RoleId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof RoleId ? $value->value() : (string) $value;
    }
}
