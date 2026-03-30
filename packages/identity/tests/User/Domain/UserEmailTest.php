<?php
declare(strict_types=1);

namespace Identity\Tests\User\Domain;

use Identity\User\Domain\UserEmail;
use PHPUnit\Framework\TestCase;

final class UserEmailTest extends TestCase
{
    public function test_creates_from_valid_email(): void
    {
        $email = UserEmail::fromString('Admin@ERP.Local');
        $this->assertSame('admin@erp.local', $email->value());
    }

    public function test_throws_on_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        UserEmail::fromString('not-an-email');
    }

    public function test_equality(): void
    {
        $email1 = UserEmail::fromString('admin@erp.local');
        $email2 = UserEmail::fromString('ADMIN@ERP.LOCAL');
        $this->assertTrue($email1->equals($email2));
    }

    public function test_to_string(): void
    {
        $email = UserEmail::fromString('admin@erp.local');
        $this->assertSame('admin@erp.local', (string) $email);
    }
}
