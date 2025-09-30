<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Checkout;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Checkout\Message;

final class MessageTest extends TestCase
{
    public function testFromArrayCreatesInfoMessage(): void
    {
        $message = Message::fromArray([
            'type' => 'info',
            'content_type' => 'plain',
            'content' => 'Your order is being prepared.',
        ]);

        $this->assertSame('info', $message->getType());
        $this->assertSame('plain', $message->getContentType());
        $this->assertSame('Your order is being prepared.', $message->getContent());
    }

    public function testRejectsErrorWithoutCode(): void
    {
        $this->expectException(ValidationException::class);

        Message::fromArray([
            'type' => 'error',
            'content_type' => 'plain',
            'content' => 'Payment declined',
        ]);
    }

    public function testRejectsErrorWithUnsupportedCode(): void
    {
        $this->expectException(ValidationException::class);

        Message::fromArray([
            'type' => 'error',
            'code' => 'unexpected_code',
            'content_type' => 'plain',
            'content' => 'Unknown error',
        ]);
    }

    public function testAllowsErrorWithSupportedCode(): void
    {
        $message = Message::fromArray([
            'type' => 'error',
            'code' => 'payment_declined',
            'content_type' => 'plain',
            'content' => 'Payment declined',
        ]);

        $this->assertSame('payment_declined', $message->getCode());
    }
}
