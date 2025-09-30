<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Checkout;

use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Checkout\FulfillmentOption;

final class FulfillmentOptionTest extends TestCase
{
    public function testFromArrayCreatesShippingOption(): void
    {
        $option = FulfillmentOption::fromArray([
            'type' => 'shipping',
            'id' => 'ship_1',
            'title' => 'Express',
            'subtotal' => 500,
            'tax' => 50,
            'total' => 550,
        ]);

        $this->assertSame('shipping', $option->getType());
        $this->assertSame('ship_1', $option->getId());
        $this->assertSame(550, $option->getTotal());
    }
}
