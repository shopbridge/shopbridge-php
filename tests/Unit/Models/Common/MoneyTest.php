<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Common;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Currency;
use ShopBridge\Models\Common\Money;

final class MoneyTest extends TestCase
{
    public function testToArray(): void
    {
        $money = new Money(100, new Currency('usd'));

        $this->assertSame(
            [
                'amount' => 100,
                'currency' => 'usd',
            ],
            $money->toArray()
        );
    }

    public function testRejectsNegativeAmounts(): void
    {
        $this->expectException(ValidationException::class);
        new Money(-1, new Currency('usd'));
    }
}
