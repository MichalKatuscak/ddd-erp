<?php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\User\Domain\UserPassword;
use PHPUnit\Framework\TestCase;

final class UserPasswordTest extends TestCase
{
    public function test_hashes_plaintext_password(): void
    {
        $password = UserPassword::fromPlaintext('SecurePass123!');
        $this->assertNotSame('SecurePass123!', $password->hash());
        $this->assertStringStartsWith('$2y$', $password->hash());
    }

    public function test_verify_correct_password(): void
    {
        $password = UserPassword::fromPlaintext('SecurePass123!');
        $this->assertTrue($password->verify('SecurePass123!'));
    }

    public function test_verify_wrong_password(): void
    {
        $password = UserPassword::fromPlaintext('SecurePass123!');
        $this->assertFalse($password->verify('WrongPassword'));
    }

    public function test_throws_on_too_short_password(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserPassword::fromPlaintext('short');
    }

    public function test_creates_from_existing_hash(): void
    {
        $original = UserPassword::fromPlaintext('SecurePass123!');
        $restored = UserPassword::fromHash($original->hash());
        $this->assertTrue($restored->verify('SecurePass123!'));
    }
}
