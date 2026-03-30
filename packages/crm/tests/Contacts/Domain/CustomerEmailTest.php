<?php
declare(strict_types=1);

namespace Crm\Tests\Contacts\Domain;

use Crm\Contacts\Domain\CustomerEmail;
use Crm\Contacts\Domain\InvalidEmailException;
use PHPUnit\Framework\TestCase;

final class CustomerEmailTest extends TestCase
{
    public function test_creates_from_valid_email(): void
    {
        $email = CustomerEmail::fromString('Jan.Novak@Firma.CZ');
        $this->assertSame('jan.novak@firma.cz', $email->value());
    }

    public function test_throws_on_invalid_email(): void
    {
        $this->expectException(InvalidEmailException::class);
        CustomerEmail::fromString('not-an-email');
    }

    public function test_equality(): void
    {
        $email1 = CustomerEmail::fromString('jan@firma.cz');
        $email2 = CustomerEmail::fromString('JAN@FIRMA.CZ');
        $this->assertTrue($email1->equals($email2));
    }
}
