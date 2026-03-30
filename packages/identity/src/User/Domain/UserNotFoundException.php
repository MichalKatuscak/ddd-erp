<?php
declare(strict_types=1);

namespace Identity\User\Domain;

final class UserNotFoundException extends \DomainException
{
    public function __construct(string $id)
    {
        parent::__construct("User not found: '$id'");
    }
}
