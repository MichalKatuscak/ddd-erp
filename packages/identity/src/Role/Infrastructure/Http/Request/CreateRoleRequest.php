<?php
declare(strict_types=1);

namespace Identity\Role\Infrastructure\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateRoleRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 1, max: 100)]
        public readonly string $name = '',

        #[Assert\NotNull]
        #[Assert\Count(min: 1)]
        #[Assert\All([new Assert\NotBlank()])]
        public readonly ?array $permissions = null,
    ) {}
}
