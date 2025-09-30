<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Common;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Address;

final class AddressTest extends TestCase
{
    public function testItSerializesToArray(): void
    {
        $address = new Address(
            'John Doe',
            '123 Main St',
            'Apt 4',
            'San Francisco',
            'CA',
            'US',
            '94105',
            '+15551234567'
        );

        $this->assertSame(
            [
                'name' => 'John Doe',
                'line_one' => '123 Main St',
                'city' => 'San Francisco',
                'country' => 'US',
                'postal_code' => '94105',
                'state' => 'CA',
                'line_two' => 'Apt 4',
            ],
            $address->toArray()
        );
    }

    public function testItRejectsEmptyName(): void
    {
        $this->expectException(ValidationException::class);

        new Address('', '123', null, 'City', 'CA', 'US', '00000');
    }

    public function testItRejectsInvalidCountry(): void
    {
        $this->expectException(ValidationException::class);

        new Address('John', '123', null, 'City', 'CA', 'United States', '00000');
    }
}
