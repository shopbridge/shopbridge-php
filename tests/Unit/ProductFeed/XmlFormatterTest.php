<?php

declare(strict_types=1);

namespace Tests\Unit\ProductFeed;

use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Common\Currency;
use ShopBridge\Models\Common\Money;
use ShopBridge\ProductFeed\Product;
use ShopBridge\ProductFeed\ProductFeedBuilder;
use ShopBridge\ProductFeed\ProductCompliance;
use ShopBridge\ProductFeed\XmlFormatter;

final class XmlFormatterTest extends TestCase
{
    public function testGeneratesXml(): void
    {
        $formatter = new XmlFormatter();
        $builder = new ProductFeedBuilder(null, $formatter);

        $product = new Product(
            'sku_xml',
            'XML Product',
            'XML description',
            'https://merchant.example.com/xml-product',
            new Money(2500, new Currency('usd')),
            true,
            true,
            'in_stock',
            5,
            'https://merchant.example.com/images/xml-product/main.jpg',
            ['https://merchant.example.com/images/xml-product/alt1.jpg', 'https://merchant.example.com/images/xml-product/alt2.jpg'],
            null,
            null,
            'ShopBridge',
            null,
            'XML-MPN',
            null,
            new ProductCompliance('warning'),
            ['custom_attribute' => 'value']
        );

        $xml = $builder->buildString([$product]);

        $this->assertStringContainsString('<products>', $xml);
        $this->assertStringContainsString('<product>', $xml);
        $this->assertStringContainsString('<id>sku_xml</id>', $xml);
        $this->assertStringContainsString('<price>', $xml);
        $this->assertSame('application/xml', $formatter->getContentType());

        $sxe = new \SimpleXMLElement($xml);
        $this->assertSame('sku_xml', (string) $sxe->product->id);
        $this->assertSame('value', (string) $sxe->product->custom_attribute);
        $this->assertSame('true', (string) $sxe->product->enable_search);
    }
}
