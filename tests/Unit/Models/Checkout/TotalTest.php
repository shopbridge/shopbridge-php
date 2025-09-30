<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Checkout;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Checkout\Total;

final class TotalTest extends TestCase
{
    public function testFromArrayCreatesTotal(): void
    {
        $total = Total::fromArray([
            'type' => 'subtotal',
            'display_text' => 'Subtotal',
            'amount' => 1000,
        ]);

        $this->assertSame('subtotal', $total->getType());
        $this->assertSame('Subtotal', $total->getDisplayText());
        $this->assertSame(1000, $total->getAmount());
    }

    public function testRejectsUnknownType(): void
    {
        $this->expectException(ValidationException::class);
        Total::fromArray([
            'type' => 'unknown',
            'display_text' => 'Foo',
            'amount' => 0,
        ]);
    }
}
