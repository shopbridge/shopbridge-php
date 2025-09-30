<?php

declare(strict_types=1);

namespace Tests\Unit\ProductFeed;

use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Common\Currency;
use ShopBridge\Models\Common\Money;
use ShopBridge\ProductFeed\Product;
use ShopBridge\ProductFeed\ProductFeedBuilder;
use ShopBridge\ProductFeed\TsvFormatter;

final class TsvFormatterTest extends TestCase
{
    public function testUsesTabDelimiters(): void
    {
        $builder = new ProductFeedBuilder(null, new TsvFormatter());

        $product = new Product(
            'sku_tsv',
            'TSV Product',
            'Sample description',
            'https://merchant.example.com/tsv-product',
            new Money(1000, new Currency('usd')),
            true,
            true,
            'in_stock',
            10,
            'https://merchant.example.com/images/tsv-product/main.jpg'
        );

        $tsv = $builder->buildString([$product]);
        $lines = array_values(array_filter(explode("\n", $tsv)));
        $header = explode("\t", $lines[0]);
        $row = explode("\t", $lines[1]);

        $this->assertSame('text/tab-separated-values', (new TsvFormatter())->getContentType());
        $this->assertSame('sku_tsv', $row[0]);
        $this->assertSame('in_stock', $row[array_search('availability', $header, true)]);
    }
}
