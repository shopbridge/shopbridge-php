<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Common;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Currency;

final class CurrencyTest extends TestCase
{
    public function testNormalizesToLowercase(): void
    {
        $currency = new Currency('USD');

        $this->assertSame('usd', $currency->getCode());
        $this->assertSame('usd', (string) $currency);
    }

    public function testRejectsInvalidCode(): void
    {
        $this->expectException(ValidationException::class);
        new Currency('US');
    }
}
