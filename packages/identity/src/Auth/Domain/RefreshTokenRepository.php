<?php
// packages/identity/src/Auth/Domain/RefreshTokenRepository.php
declare(strict_types=1);

namespace Identity\Auth\Domain;

interface RefreshTokenRepository
{
    public function findByTokenHash(string $hash): ?RefreshToken;

    public function save(RefreshToken $token): void;
}
