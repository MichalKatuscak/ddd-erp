<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        public readonly string $password = '',
    ) {}
}
