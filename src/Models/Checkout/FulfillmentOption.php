<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class FulfillmentOption
{
    private string $type;
    private string $id;
    private string $title;
    private ?string $subtitle;
    private ?string $carrier;
    private ?string $earliestDeliveryTime;
    private ?string $latestDeliveryTime;
    private int $subtotal;
    private int $tax;
    private int $total;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(
        string $type,
        string $id,
        string $title,
        ?string $subtitle,
        ?string $carrier,
        ?string $earliest,
        ?string $latest,
        int $subtotal,
        int $tax,
        int $total,
        array $payload
    ) {
        if (!in_array($type, ['shipping', 'digital'], true)) {
            throw new ValidationException('fulfillment option type must be shipping or digital');
        }
        if ('' === trim($id)) {
            throw new ValidationException('fulfillment option id cannot be empty');
        }
        if ('' === trim($title)) {
            throw new ValidationException('fulfillment option title cannot be empty');
        }

        $this->type = $type;
        $this->id = $id;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->carrier = $carrier;
        $this->earliestDeliveryTime = $earliest;
        $this->latestDeliveryTime = $latest;
        $this->subtotal = $subtotal;
        $this->tax = $tax;
        $this->total = $total;
        $this->rawPayload = $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $type = (string) ($payload['type'] ?? '');

        $earliest = $payload['earliest_delivery_time'] ?? null;
        $latest = $payload['latest_delivery_time'] ?? null;
        $carrier = $payload['carrier'] ?? null;

        if ($type === 'digital') {
            $earliest = null;
            $latest = null;
            $carrier = null;
        }

        return new self(
            $type,
            (string) ($payload['id'] ?? ''),
            (string) ($payload['title'] ?? ''),
            isset($payload['subtitle']) ? (string) $payload['subtitle'] : null,
            $carrier !== null ? (string) $carrier : null,
            $earliest !== null ? (string) $earliest : null,
            $latest !== null ? (string) $latest : null,
            isset($payload['subtotal']) ? (int) $payload['subtotal'] : 0,
            isset($payload['tax']) ? (int) $payload['tax'] : 0,
            isset($payload['total']) ? (int) $payload['total'] : 0,
            $payload
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function getEarliestDeliveryTime(): ?string
    {
        return $this->earliestDeliveryTime;
    }

    public function getLatestDeliveryTime(): ?string
    {
        return $this->latestDeliveryTime;
    }

    public function getSubtotal(): int
    {
        return $this->subtotal;
    }

    public function getTax(): int
    {
        return $this->tax;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
