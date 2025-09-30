<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class OrderReference
{
    private string $id;
    private string $checkoutSessionId;
    private string $permalinkUrl;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $rawPayload
     */
    private function __construct(string $id, string $checkoutSessionId, string $permalinkUrl, array $rawPayload)
    {
        if ('' === trim($id)) {
            throw new ValidationException('order id cannot be empty');
        }

        if ('' === trim($checkoutSessionId)) {
            throw new ValidationException('checkout_session_id cannot be empty');
        }

        if ('' === trim($permalinkUrl)) {
            throw new ValidationException('permalink_url cannot be empty');
        }

        $this->id = $id;
        $this->checkoutSessionId = $checkoutSessionId;
        $this->permalinkUrl = $permalinkUrl;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['id'] ?? ''),
            (string) ($payload['checkout_session_id'] ?? ''),
            (string) ($payload['permalink_url'] ?? ''),
            $payload
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCheckoutSessionId(): string
    {
        return $this->checkoutSessionId;
    }

    public function getPermalinkUrl(): string
    {
        return $this->permalinkUrl;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
