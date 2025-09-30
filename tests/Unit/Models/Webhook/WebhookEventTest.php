<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Webhook;

use PHPUnit\Framework\TestCase;
use ShopBridge\Models\Webhook\WebhookEvent;

final class WebhookEventTest extends TestCase
{
    public function testFromArrayCreatesEvent(): void
    {
        $payload = [
            'type' => 'order_create',
            'data' => [
                'type' => 'order',
                'checkout_session_id' => 'cs_123',
                'permalink_url' => 'https://example.com/orders/123',
                'status' => 'created',
                'refunds' => [
                    [
                        'type' => 'store_credit',
                        'amount' => 100,
                    ],
                ],
            ],
        ];

        $event = WebhookEvent::fromArray($payload);

        $this->assertSame('order_create', $event->getType());
        $this->assertSame('cs_123', $event->getData()->getCheckoutSessionId());
        $this->assertSame('https://example.com/orders/123', $event->getData()->getPermalinkUrl());
        $this->assertSame('created', $event->getData()->getStatus());
        $this->assertCount(1, $event->getData()->getRefunds());
        $this->assertSame('order_create', $event->getCanonicalType());
    }

    public function testNormalizesLegacyType(): void
    {
        $payload = [
            'type' => 'order_updated',
            'data' => [
                'type' => 'order',
                'checkout_session_id' => 'cs_123',
                'permalink_url' => 'https://example.com/orders/123',
                'status' => 'created',
                'refunds' => [],
            ],
        ];

        $event = WebhookEvent::fromArray($payload);

        $this->assertSame('order_updated', $event->getType());
        $this->assertSame('order_update', $event->getCanonicalType());
    }
}
