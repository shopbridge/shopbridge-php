<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use ShopBridge\Contracts\SignatureValidatorInterface;
use ShopBridge\Exceptions\InvalidSignatureException;
use ShopBridge\Exceptions\TransportException;
use ShopBridge\Services\WebhookService;

final class WebhookServiceTest extends TestCase
{
    public function testParsesEvent(): void
    {
        $payload = json_encode([
            'type' => 'order_update',
            'data' => [
                'type' => 'order',
                'checkout_session_id' => 'cs_123',
                'permalink_url' => 'https://example.com/orders/123',
                'status' => 'shipped',
                'refunds' => [],
            ],
        ]);

        $validator = $this->createMock(SignatureValidatorInterface::class);
        $validator->expects($this->once())
            ->method('validate')
            ->with($payload, 'signature', 'secret');

        $service = new WebhookService($validator);
        $event = $service->parseWebhook($payload, 'signature', 'secret');

        $this->assertSame('order_update', $event->getType());
        $this->assertSame('shipped', $event->getData()->getStatus());
    }

    public function testThrowsWhenSignatureInvalid(): void
    {
        $this->expectException(InvalidSignatureException::class);

        $validator = $this->createMock(SignatureValidatorInterface::class);
        $validator->method('validate')->willThrowException(new InvalidSignatureException());

        $service = new WebhookService($validator);
        $service->parseWebhook('{}', 'sig', 'secret');
    }

    public function testThrowsWhenPayloadInvalidJson(): void
    {
        $validator = $this->createMock(SignatureValidatorInterface::class);
        $service = new WebhookService($validator);

        $this->expectException(TransportException::class);
        $service->parseWebhook("{invalid", 'sig', 'secret');
    }
}
