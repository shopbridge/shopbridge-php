<?php

declare(strict_types=1);

namespace ShopBridge\Contracts;

interface SignatureGeneratorInterface
{
    /**
     * Generate request signature for ACP headers.
     */
    /**
     * @param array<string, mixed>|null $payload
     */
    public function generate(string $method, string $path, ?array $payload, string $timestamp, string $requestId): string;
}
