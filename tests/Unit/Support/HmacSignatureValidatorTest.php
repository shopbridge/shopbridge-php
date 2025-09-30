<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use PHPUnit\Framework\TestCase;
use ShopBridge\Exceptions\InvalidSignatureException;
use ShopBridge\Support\HmacSignatureValidator;

final class HmacSignatureValidatorTest extends TestCase
{
    public function testValidSignature(): void
    {
        $validator = new HmacSignatureValidator('secret');
        $payload = json_encode(['foo' => 'bar']);
        $signature = $this->encodeBase64Url(hash_hmac('sha256', $payload, 'secret', true));

        $validator->validate($payload, $signature, 'secret');
        $this->addToAssertionCount(1);
    }

    public function testInvalidSignatureThrows(): void
    {
        $validator = new HmacSignatureValidator('secret');
        $this->expectException(InvalidSignatureException::class);
        $validator->validate('payload', 'invalid', 'secret');
    }

    public function testUsesDefaultSecretWhenNotProvided(): void
    {
        $validator = new HmacSignatureValidator('secret');
        $payload = 'payload';
        $signature = $this->encodeBase64Url(hash_hmac('sha256', $payload, 'secret', true));

        $validator->validate($payload, $signature, '');
        $this->addToAssertionCount(1);
}

    private function encodeBase64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
