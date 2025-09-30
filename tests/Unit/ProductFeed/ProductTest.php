<?php

declare(strict_types=1);

namespace Tests\Unit\ProductFeed;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Common\Currency;
use ShopBridge\Models\Common\Money;
use ShopBridge\ProductFeed\Product;
use ShopBridge\ProductFeed\ProductCompliance;

final class ProductTest extends TestCase
{
    public function testToArrayIncludesAvailabilityAndMedia(): void
    {
        $money = new Money(1099, new Currency('usd'));
        $availabilityDate = new DateTimeImmutable('2030-01-01T00:00:00Z');
        $compliance = new ProductCompliance('Contains lithium battery', null, 18);

        $product = new Product(
            'sku_1',
            'Example Product',
            'An example product used for testing.',
            'https://merchant.example.com/products/sku_1',
            $money,
            true,
            true,
            'preorder',
            500,
            'https://merchant.example.com/images/sku_1/main.jpg',
            ['https://merchant.example.com/images/sku_1/alt.jpg'],
            'https://videos.example.com/sku_1.mp4',
            null,
            'ShopBridge',
            '012345678905',
            'MPN-123',
            $availabilityDate,
            $compliance,
            ['custom_label' => 'featured']
        );

        $payload = $product->toArray();

        $this->assertSame('preorder', $payload['availability']);
        $this->assertSame(500, $payload['inventory_quantity']);
        $this->assertSame('https://merchant.example.com/images/sku_1/main.jpg', $payload['image_link']);
        $this->assertSame(['https://merchant.example.com/images/sku_1/alt.jpg'], $payload['additional_image_link']);
        $this->assertSame('https://videos.example.com/sku_1.mp4', $payload['video_link']);
        $this->assertSame('ShopBridge', $payload['brand']);
        $this->assertSame('012345678905', $payload['gtin']);
        $this->assertSame('MPN-123', $payload['mpn']);
        $this->assertSame('featured', $payload['custom_label']);
        $this->assertSame(
            ['warning' => 'Contains lithium battery', 'age_restriction' => 18],
            $payload['compliance']
        );
        $this->assertSame('2030-01-01T00:00:00+00:00', $payload['availability_date']);
    }
}

