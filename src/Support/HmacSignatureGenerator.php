<?php

declare(strict_types=1);

namespace ShopBridge\Support;

use ShopBridge\Contracts\SignatureGeneratorInterface;

final class HmacSignatureGenerator implements SignatureGeneratorInterface
{
    private string $secret;
    private string $algorithm;

    /**
     */
    public function __construct(string $secret, string $algorithm = 'sha256')
    {
        $this->secret = $secret;
        $this->algorithm = $algorithm;
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public function generate(string $method, string $path, ?array $payload, string $timestamp, string $requestId): string
    {
        $body = '';

        if ($payload !== null) {
            $body = CanonicalJson::encode($payload);
        }

        $canonical = strtoupper($method) . "\n" . $path . "\n" . $timestamp . "\n" . $requestId . "\n" . $body;
        $hash = hash_hmac($this->algorithm, $canonical, $this->secret, true);

        return $this->encodeBase64Url($hash);
    }

    private function encodeBase64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
