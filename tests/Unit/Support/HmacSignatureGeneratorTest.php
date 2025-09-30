<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use ShopBridge\Support\HmacSignatureGenerator;

final class HmacSignatureGeneratorTest extends TestCase
{
    public function testCanonicalJsonOrderingProducesStableSignature(): void
    {
        $generator = new HmacSignatureGenerator('secret');

        $payloadA = [
            'b' => 1,
            'a' => 2,
            'nested' => [
                'y' => 'value_y',
                'x' => 'value_x',
            ],
        ];

        $payloadB = [
            'nested' => [
                'x' => 'value_x',
                'y' => 'value_y',
            ],
            'a' => 2,
            'b' => 1,
        ];

        $signatureA = $generator->generate('POST', '/path', $payloadA, '2025-01-01T00:00:00Z', 'req_1');
        $signatureB = $generator->generate('POST', '/path', $payloadB, '2025-01-01T00:00:00Z', 'req_1');

        $this->assertSame($signatureA, $signatureB);
    }

    public function testCanonicalJsonHandlesNumericArrays(): void
    {
        $generator = new HmacSignatureGenerator('secret');

        $payload = [
            'items' => [
                ['id' => 'sku1', 'quantity' => 1],
                ['quantity' => 2, 'id' => 'sku2'],
            ],
        ];

        $signature = $generator->generate('POST', '/path', $payload, '2025-01-01T00:00:00Z', 'req_1');

        $this->assertNotEmpty($signature);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $signature);
    }
}
