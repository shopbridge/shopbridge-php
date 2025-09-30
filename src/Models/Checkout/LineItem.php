<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class LineItem
{
    private string $id;
    private Item $item;
    private int $baseAmount;
    private int $discount;
    private int $subtotal;
    private int $tax;
    private int $total;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $rawPayload
     */
    private function __construct(
        string $id,
        Item $item,
        int $baseAmount,
        int $discount,
        int $subtotal,
        int $tax,
        int $total,
        array $rawPayload
    ) {
        if ('' === trim($id)) {
            throw new ValidationException('line item id cannot be empty');
        }

        $this->id = $id;
        $this->item = $item;
        $this->baseAmount = $baseAmount;
        $this->discount = $discount;
        $this->subtotal = $subtotal;
        $this->tax = $tax;
        $this->total = $total;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['id'] ?? ''),
            Item::fromArray((array) ($payload['item'] ?? [])),
            isset($payload['base_amount']) ? (int) $payload['base_amount'] : 0,
            isset($payload['discount']) ? (int) $payload['discount'] : 0,
            isset($payload['subtotal']) ? (int) $payload['subtotal'] : 0,
            isset($payload['tax']) ? (int) $payload['tax'] : 0,
            isset($payload['total']) ? (int) $payload['total'] : 0,
            $payload
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getBaseAmount(): int
    {
        return $this->baseAmount;
    }

    public function getDiscount(): int
    {
        return $this->discount;
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
