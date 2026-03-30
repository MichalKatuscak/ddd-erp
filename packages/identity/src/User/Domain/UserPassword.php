<?php
declare(strict_types=1);

namespace Identity\User\Domain;

final class UserPassword
{
    private const int MIN_LENGTH = 8;

    private function __construct(
        private readonly string $hash,
    ) {}

    public static function fromPlaintext(string $plaintext): self
    {
        if (mb_strlen($plaintext) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Password must be at least %d characters', self::MIN_LENGTH),
            );
        }
        return new self(password_hash($plaintext, PASSWORD_BCRYPT));
    }

    public static function fromHash(string $hash): self
    {
        if ($hash === '') {
            throw new \InvalidArgumentException('Password hash cannot be empty');
        }
        return new self($hash);
    }

    public function hash(): string { return $this->hash; }

    public function verify(string $plaintext): bool
    {
        return password_verify($plaintext, $this->hash);
    }
}
