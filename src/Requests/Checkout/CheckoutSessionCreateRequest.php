<?php

declare(strict_types=1);

namespace ShopBridge\Requests\Checkout;

use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Address;
use ShopBridge\Models\Common\Buyer;

final class CheckoutSessionCreateRequest
{
    /** @var CheckoutSessionItemRequest[] */
    private array $items;
    private ?Buyer $buyer;
    private ?Address $fulfillmentAddress;

    /**
     * @param array<int, mixed> $items
     */
    public function __construct(array $items, ?Buyer $buyer = null, ?Address $fulfillmentAddress = null)
    {
        if ($items === []) {
            throw new ValidationException('create session requires at least one item');
        }

        $validatedItems = [];
        foreach ($items as $item) {
            if (!$item instanceof CheckoutSessionItemRequest) {
                throw new ValidationException('items must be instance of CheckoutSessionItemRequest');
            }

            $validatedItems[] = $item;
        }

        $this->items = $validatedItems;
        $this->buyer = $buyer;
        $this->fulfillmentAddress = $fulfillmentAddress;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'items' => array_map(static fn (CheckoutSessionItemRequest $item): array => $item->toArray(), $this->items),
        ];

        if ($this->buyer !== null) {
            $payload['buyer'] = $this->buyer->toArray();
        }

        if ($this->fulfillmentAddress !== null) {
            $payload['fulfillment_address'] = $this->fulfillmentAddress->toArray();
        }

        return $payload;
    }
}
