<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Checkout;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Checkout\PaymentProvider;

final class PaymentProviderTest extends TestCase
{
    public function testFromArrayCreatesProvider(): void
    {
        $provider = PaymentProvider::fromArray([
            'provider' => 'stripe',
            'supported_payment_methods' => ['card'],
        ]);

        $this->assertSame('stripe', $provider->getProvider());
        $this->assertSame(['card'], $provider->getSupportedMethods());
    }

    public function testThrowsOnMissingSupportedMethods(): void
    {
        $this->expectException(ValidationException::class);

        PaymentProvider::fromArray([
            'provider' => 'stripe',
        ]);
    }
}
