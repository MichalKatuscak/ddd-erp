<?php
declare(strict_types=1);

namespace Identity\Auth\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class RefreshAccessTokenRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $refresh_token = '',
    ) {}
}
