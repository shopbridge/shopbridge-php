<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Common;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Buyer;

final class BuyerTest extends TestCase
{
    public function testToArray(): void
    {
        $buyer = new Buyer('John', 'Doe', 'john@example.com', '+15551234567');

        $this->assertSame(
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone_number' => '+15551234567',
            ],
            $buyer->toArray()
        );
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(ValidationException::class);
        new Buyer('John', 'Doe', 'not-an-email');
    }
}
