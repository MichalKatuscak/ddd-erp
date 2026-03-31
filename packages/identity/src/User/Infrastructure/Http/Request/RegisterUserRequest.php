<?php
declare(strict_types=1);

namespace Identity\User\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 255)]
        public readonly string $password = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $first_name = '',

        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $last_name = '',
    ) {}
}
