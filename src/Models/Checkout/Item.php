<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class Item
{
    private string $id;
    private int $quantity;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $rawPayload
     */
    private function __construct(string $id, int $quantity, array $rawPayload)
    {
        if ('' === trim($id)) {
            throw new ValidationException('item id cannot be empty');
        }

        if ($quantity <= 0) {
            throw new ValidationException('item quantity must be greater than zero');
        }

        $this->id = $id;
        $this->quantity = $quantity;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['id'] ?? ''),
            isset($payload['quantity']) ? (int) $payload['quantity'] : 0,
            $payload
        );
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
