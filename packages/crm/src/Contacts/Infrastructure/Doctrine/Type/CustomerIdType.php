<?php
declare(strict_types=1);

namespace Crm\Contacts\Infrastructure\Doctrine\Type;

use Crm\Contacts\Domain\CustomerId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class CustomerIdType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CustomerId
    {
        if ($value === null) {
            return null;
        }
        return CustomerId::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }
        return $value instanceof CustomerId ? $value->value() : (string) $value;
    }

}
