<?php

declare(strict_types=1);

namespace ShopBridge\Support;

use ShopBridge\Contracts\SignatureValidatorInterface;
use ShopBridge\Exceptions\InvalidSignatureException;

final class HmacSignatureValidator implements SignatureValidatorInterface
{
    private string $secret;
    private string $algorithm;

    public function __construct(string $secret, string $algorithm = 'sha256')
    {
        $this->secret = $secret;
        $this->algorithm = $algorithm;
    }

    public function validate(string $payload, string $signature, string $secret = ''): void
    {
        $key = $secret !== '' ? $secret : $this->secret;
        $expected = hash_hmac($this->algorithm, $payload, $key, true);
        $expectedSignature = rtrim(strtr(base64_encode($expected), '+/', '-_'), '=');

        if (!$this->hashEquals($expectedSignature, $signature)) {
            throw new InvalidSignatureException('Webhook signature verification failed.');
        }
    }

    private function hashEquals(string $known, string $user): bool
    {
        if (!hash_equals($known, $user)) {
            // Allow incoming signature to include padding. Normalize and compare again.
            $normalizedUser = rtrim($user, '=');

            return hash_equals($known, $normalizedUser);
        }

        return true;
    }
}
