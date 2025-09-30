<?php

declare(strict_types=1);

namespace ShopBridge\Models\Webhook;

use ShopBridge\Exceptions\ValidationException;

final class WebhookEvent
{
    private string $type;
    private string $canonicalType;
    private EventDataOrder $data;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $rawPayload
     */
    private function __construct(string $type, EventDataOrder $data, array $rawPayload)
    {
        $normalizedType = self::normalizeType($type);

        if (!in_array($normalizedType, ['order_create', 'order_update'], true)) {
            throw new ValidationException('Webhook event type must be order_create or order_update');
        }

        $this->type = $type;
        $this->canonicalType = $normalizedType;
        $this->data = $data;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['type'] ?? ''),
            EventDataOrder::fromArray((array) ($payload['data'] ?? [])),
            $payload
        );
    }

    private static function normalizeType(string $type): string
    {
        switch ($type) {
            case 'order_created':
                return 'order_create';
            case 'order_updated':
                return 'order_update';
            default:
                return $type;
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCanonicalType(): string
    {
        return $this->canonicalType;
    }

    public function getData(): EventDataOrder
    {
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
