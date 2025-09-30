<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Checkout;

use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Checkout\Link;

final class LinkTest extends TestCase
{
    public function testFromArrayCreatesLink(): void
    {
        $link = Link::fromArray([
            'type' => 'terms_of_use',
            'url' => 'https://example.com/terms',
        ]);

        $this->assertSame('terms_of_use', $link->getType());
        $this->assertSame('https://example.com/terms', $link->getUrl());
    }
}
