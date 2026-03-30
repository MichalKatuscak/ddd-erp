<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\User\Domain\UserPassword;

final class UserPasswordType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?UserPassword
    {
        if ($value === null) {
            return null;
        }
        return UserPassword::fromHash((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof UserPassword ? $value->hash() : (string) $value;
    }
}
