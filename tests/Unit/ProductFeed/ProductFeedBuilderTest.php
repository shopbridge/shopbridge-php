<?php

declare(strict_types=1);

namespace Tests\Unit\ProductFeed;

use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Common\Currency;
use ShopBridge\Models\Common\Money;
use ShopBridge\ProductFeed\Product;
use ShopBridge\ProductFeed\ProductFeedBuilder;

final class ProductFeedBuilderTest extends TestCase
{
    public function testBuildStreaming(): void
    {
        $builder = new ProductFeedBuilder();
        $products = [
            $this->product('sku1', 'Product 1'),
            $this->product('sku2', 'Product 2'),
        ];

        $chunks = iterator_to_array($builder->build($products));

        $json = implode('', $chunks);
        $decoded = json_decode($json, true);

        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertSame('sku1', $decoded[0]['id']);
        $this->assertSame('in_stock', $decoded[0]['availability']);
        $this->assertSame('https://example.com/sku1/image.jpg', $decoded[0]['image_link']);
    }

    public function testBuildString(): void
    {
        $builder = new ProductFeedBuilder();
        $output = $builder->buildString([$this->product('sku1', 'Product 1')]);

        $decoded = json_decode($output, true);
        $this->assertIsArray($decoded);
        $this->assertSame('sku1', $decoded[0]['id']);
        $this->assertSame(25, $decoded[0]['inventory_quantity']);
    }

    private function product(string $id, string $title): Product
    {
        return new Product(
            $id,
            $title,
            'Desc',
            'https://example.com/' . $id,
            new Money(1000, new Currency('usd')),
            true,
            true,
            'in_stock',
            25,
            'https://example.com/' . $id . '/image.jpg'
        );
    }
}
