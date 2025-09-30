<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class Total
{
    private string $type;
    private string $displayText;
    private int $amount;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $rawPayload
     */
    private function __construct(string $type, string $displayText, int $amount, array $rawPayload)
    {
        $allowedTypes = [
            'items_base_amount',
            'items_discount',
            'subtotal',
            'discount',
            'fulfillment',
            'tax',
            'fee',
            'total',
        ];

        if (!in_array($type, $allowedTypes, true)) {
            throw new ValidationException('unsupported total type');
        }

        $this->type = $type;
        $this->displayText = $displayText;
        $this->amount = $amount;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['type'] ?? ''),
            (string) ($payload['display_text'] ?? ''),
            isset($payload['amount']) ? (int) $payload['amount'] : 0,
            $payload
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getDisplayText(): string
    {
        return $this->displayText;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
