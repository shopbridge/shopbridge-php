<?php

declare(strict_types=1);

namespace ShopBridge\Models\Webhook;

use ShopBridge\Exceptions\ValidationException;

final class EventDataOrder
{
    private string $type;
    private string $checkoutSessionId;
    private string $permalinkUrl;
    private string $status;
    /** @var Refund[] */
    private array $refunds;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param Refund[]                $refunds
     * @param array<string, mixed>    $rawPayload
     */
    private function __construct(string $type, string $checkoutSessionId, string $permalinkUrl, string $status, array $refunds, array $rawPayload)
    {
        if ($type !== 'order') {
            throw new ValidationException('event data type must be order');
        }

        if ('' === trim($checkoutSessionId)) {
            throw new ValidationException('checkout_session_id cannot be empty');
        }

        if ('' === trim($permalinkUrl)) {
            throw new ValidationException('permalink_url cannot be empty');
        }

        if (!in_array($status, ['created', 'manual_review', 'confirmed', 'canceled', 'shipped', 'fulfilled'], true)) {
            throw new ValidationException('unsupported order status');
        }

        $this->type = $type;
        $this->checkoutSessionId = $checkoutSessionId;
        $this->permalinkUrl = $permalinkUrl;
        $this->status = $status;
        $this->refunds = $refunds;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $refunds = [];
        if (isset($payload['refunds']) && is_array($payload['refunds'])) {
            foreach ($payload['refunds'] as $refund) {
                if (is_array($refund)) {
                    $refunds[] = Refund::fromArray($refund);
                }
            }
        }

        return new self(
            (string) ($payload['type'] ?? ''),
            (string) ($payload['checkout_session_id'] ?? ''),
            (string) ($payload['permalink_url'] ?? ''),
            (string) ($payload['status'] ?? ''),
            $refunds,
            $payload
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCheckoutSessionId(): string
    {
        return $this->checkoutSessionId;
    }

    public function getPermalinkUrl(): string
    {
        return $this->permalinkUrl;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return Refund[]
     */
    public function getRefunds(): array
    {
        return $this->refunds;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
