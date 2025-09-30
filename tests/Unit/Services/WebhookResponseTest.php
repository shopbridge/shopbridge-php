<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use ShopBridge\Services\WebhookResponse;

final class WebhookResponseTest extends TestCase
{
    public function testAcknowledge(): void
    {
        $response = WebhookResponse::acknowledge('req_123');

        $this->assertSame(200, $response['status']);
        $this->assertSame('application/json', $response['headers']['Content-Type']);
        $this->assertSame(['received' => true, 'request_id' => 'req_123'], json_decode($response['body'], true));
    }

    public function testErrorResponse(): void
    {
        $error = ['type' => 'invalid_request', 'code' => 'missing'];
        $response = WebhookResponse::error(400, $error, 'req_123');

        $this->assertSame(400, $response['status']);
        $decoded = json_decode($response['body'], true);
        $this->assertSame($error, $decoded['error']);
        $this->assertSame('req_123', $decoded['request_id']);
    }
}
