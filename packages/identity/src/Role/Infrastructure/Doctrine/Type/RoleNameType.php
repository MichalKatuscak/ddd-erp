<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\Role\Domain\RoleName;

final class RoleNameType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?RoleName
    {
        if ($value === null) {
            return null;
        }
        return RoleName::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof RoleName ? $value->value() : (string) $value;
    }
}
