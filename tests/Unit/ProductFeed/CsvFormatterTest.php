<?php

declare(strict_types=1);

namespace Tests\Unit\ProductFeed;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Common\Currency;
use ShopBridge\Models\Common\Money;
use ShopBridge\ProductFeed\CsvFormatter;
use ShopBridge\ProductFeed\Product;
use ShopBridge\ProductFeed\ProductCompliance;
use ShopBridge\ProductFeed\ProductFeedBuilder;

final class CsvFormatterTest extends TestCase
{
    public function testBuildsCsvWithHeader(): void
    {
        $formatter = new CsvFormatter();
        $builder = new ProductFeedBuilder(null, $formatter);

        $product = $this->product();

        $csv = $builder->buildString([$product]);

        $lines = array_values(array_filter(explode("\n", $csv))); // remove trailing blank

        $this->assertGreaterThanOrEqual(2, count($lines));
        $header = str_getcsv($lines[0], ',', '"', '\\');
        $row = str_getcsv($lines[1], ',', '"', '\\');

        $this->assertSame('id', $header[0]);
        $this->assertSame('sku_csv', $row[0]);
        $this->assertSame('text/csv', $formatter->getContentType());
        $this->assertSame('preorder', $row[array_search('availability', $header, true)]);
        $this->assertSame('1599 usd', $row[array_search('price', $header, true)]);
    }

    private function product(): Product
    {
        return new Product(
            'sku_csv',
            'CSV Product',
            'Feed product for CSV export',
            'https://merchant.example.com/csv-product',
            new Money(1599, new Currency('usd')),
            true,
            true,
            'preorder',
            20,
            'https://merchant.example.com/images/csv-product/main.jpg',
            ['https://merchant.example.com/images/csv-product/alt.jpg'],
            null,
            null,
            'ShopBridge',
            '0123456789012',
            'MPN-CSV',
            new DateTimeImmutable('2030-01-01T00:00:00Z'),
            new ProductCompliance('warning text', null, 18),
            ['custom_label' => 'featured']
        );
    }
}
