<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Doctrine\Type;

use Crm\Contacts\Domain\CustomerEmail;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class CustomerEmailType extends StringType
{
    public const NAME = 'customer_email';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CustomerEmail
    {
        if ($value === null) {
            return null;
        }
        return CustomerEmail::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof CustomerEmail ? $value->value() : (string) $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
