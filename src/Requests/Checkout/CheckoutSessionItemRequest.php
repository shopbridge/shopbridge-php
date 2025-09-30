<?php

declare(strict_types=1);

namespace ShopBridge\Requests\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class CheckoutSessionItemRequest
{
    private string $id;
    private int $quantity;

    public function __construct(string $id, int $quantity)
    {
        if ('' === trim($id)) {
            throw new ValidationException('item id cannot be empty');
        }

        if ($quantity < 1) {
            throw new ValidationException('item quantity must be at least 1');
        }

        $this->id = $id;
        $this->quantity = $quantity;
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
     * @return array{id: string, quantity: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
        ];
    }
}

