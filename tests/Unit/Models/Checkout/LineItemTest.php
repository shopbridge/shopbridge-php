<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Checkout;

use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Checkout\Item;
use ShopBridge\Models\Checkout\LineItem;

final class LineItemTest extends TestCase
{
    public function testFromArrayCreatesLineItem(): void
    {
        $lineItem = LineItem::fromArray([
            'id' => 'item_123',
            'item' => [
                'id' => 'sku_1',
                'quantity' => 1,
            ],
            'base_amount' => 1000,
            'discount' => 100,
            'subtotal' => 900,
            'tax' => 90,
            'total' => 990,
        ]);

        $this->assertSame('item_123', $lineItem->getId());
        $this->assertInstanceOf(Item::class, $lineItem->getItem());
        $this->assertSame(1000, $lineItem->getBaseAmount());
        $this->assertSame(990, $lineItem->getTotal());
    }
}
