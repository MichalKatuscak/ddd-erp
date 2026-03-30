<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Doctrine\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Identity\User\Domain\UserEmail;

final class UserEmailType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?UserEmail
    {
        if ($value === null) {
            return null;
        }
        return UserEmail::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof UserEmail ? $value->value() : (string) $value;
    }
}
