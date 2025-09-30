<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class Link
{
    private string $type;
    private string $url;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(string $type, string $url, array $payload)
    {
        if (!in_array($type, ['terms_of_use', 'privacy_policy', 'seller_shop_policies'], true)) {
            throw new ValidationException('unsupported link type');
        }

        if ('' === trim($url)) {
            throw new ValidationException('link url cannot be empty');
        }

        $this->type = $type;
        $this->url = $url;
        $this->rawPayload = $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['type'] ?? ''),
            (string) ($payload['url'] ?? ''),
            $payload
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
