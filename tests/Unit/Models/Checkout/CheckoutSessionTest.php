<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Checkout;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Checkout\CheckoutSession;

final class CheckoutSessionTest extends TestCase
{
    public function testFromArrayBuildsImmutableGraph(): void
    {
        $payload = [
            'id' => 'cs_123',
            'status' => 'ready_for_payment',
            'currency' => 'USD',
            'line_items' => [
                [
                    'id' => 'line_1',
                    'item' => ['id' => 'sku_1', 'quantity' => 2],
                    'base_amount' => 1500,
                    'discount' => 200,
                    'subtotal' => 1300,
                    'tax' => 100,
                    'total' => 1400,
                ],
            ],
            'totals' => [
                ['type' => 'subtotal', 'display_text' => 'Subtotal', 'amount' => 1300],
                ['type' => 'tax', 'display_text' => 'Tax', 'amount' => 100],
                ['type' => 'total', 'display_text' => 'Total', 'amount' => 1400],
            ],
            'fulfillment_options' => [
                [
                    'type' => 'shipping',
                    'id' => 'ship_ground',
                    'title' => 'Ground',
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                ],
            ],
            'messages' => [],
            'links' => [
                ['type' => 'terms_of_use', 'url' => 'https://merchant.example.com/terms'],
            ],
        ];

        $session = CheckoutSession::fromArray($payload);

        $this->assertSame('cs_123', $session->getId());
        $this->assertSame('ready_for_payment', $session->getStatus());
        $this->assertSame('usd', $session->getCurrency());
        $this->assertCount(1, $session->getLineItems());
        $lineItem = $session->getLineItems()[0];
        $this->assertSame(1500, $lineItem->getBaseAmount());
        $this->assertSame(200, $lineItem->getDiscount());
        $this->assertSame(1300, $lineItem->getSubtotal());
        $this->assertSame(100, $lineItem->getTax());
        $this->assertSame(1400, $lineItem->getTotal());

        $this->assertCount(3, $session->getTotals());
        $totalTypes = array_map(static fn ($total) => $total->getType(), $session->getTotals());
        $this->assertSame(['subtotal', 'tax', 'total'], $totalTypes);

        $this->assertSame($payload, $session->toArray());
    }

    public function testFromArrayRejectsUnknownStatus(): void
    {
        $payload = [
            'id' => 'cs_invalid',
            'status' => 'unknown_state',
            'currency' => 'usd',
            'line_items' => [],
            'totals' => [],
            'fulfillment_options' => [],
            'messages' => [],
            'links' => [],
        ];

        $this->expectException(ValidationException::class);
        CheckoutSession::fromArray($payload);
    }

    public function testFromArrayRequiresCurrency(): void
    {
        $payload = [
            'id' => 'cs_missing_currency',
            'status' => 'ready_for_payment',
            'line_items' => [],
            'totals' => [],
            'fulfillment_options' => [],
            'messages' => [],
            'links' => [],
        ];

        $this->expectException(ValidationException::class);
        CheckoutSession::fromArray($payload);
    }
}

