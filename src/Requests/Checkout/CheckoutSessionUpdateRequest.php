<?php

declare(strict_types=1);

namespace ShopBridge\Requests\Checkout;

use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Address;
use ShopBridge\Models\Common\Buyer;

final class CheckoutSessionUpdateRequest
{
    /** @var CheckoutSessionItemRequest[]|null */
    private ?array $items;
    private ?Buyer $buyer;
    private ?Address $fulfillmentAddress;
    private ?string $fulfillmentOptionId;

    /**
     * @param array<int, mixed>|null $items
     */
    public function __construct(
        ?array $items = null,
        ?Buyer $buyer = null,
        ?Address $fulfillmentAddress = null,
        ?string $fulfillmentOptionId = null
    ) {
        if ($items !== null) {
            if ($items === []) {
                throw new ValidationException('items cannot be an empty array when provided');
            }

            $validatedItems = [];
            foreach ($items as $item) {
                if (!$item instanceof CheckoutSessionItemRequest) {
                    throw new ValidationException('items must be instance of CheckoutSessionItemRequest');
                }

                $validatedItems[] = $item;
            }

            $items = $validatedItems;
        }

        if ($fulfillmentOptionId !== null && '' === trim($fulfillmentOptionId)) {
            throw new ValidationException('fulfillment option id cannot be empty string');
        }

        if ($items === null && $buyer === null && $fulfillmentAddress === null && $fulfillmentOptionId === null) {
            throw new ValidationException('update request must specify at least one field');
        }

        $this->items = $items;
        $this->buyer = $buyer;
        $this->fulfillmentAddress = $fulfillmentAddress;
        $this->fulfillmentOptionId = $fulfillmentOptionId;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->items !== null) {
            $payload['items'] = array_map(static fn (CheckoutSessionItemRequest $item): array => $item->toArray(), $this->items);
        }

        if ($this->buyer !== null) {
            $payload['buyer'] = $this->buyer->toArray();
        }

        if ($this->fulfillmentAddress !== null) {
            $payload['fulfillment_address'] = $this->fulfillmentAddress->toArray();
        }

        if ($this->fulfillmentOptionId !== null) {
            $payload['fulfillment_option_id'] = $this->fulfillmentOptionId;
        }

        return $payload;
    }
}
