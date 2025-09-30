<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Address;
use ShopBridge\Models\Common\Buyer;
use ShopBridge\Models\Checkout\Message;
use ShopBridge\Models\Checkout\OrderReference;
use ShopBridge\Models\Checkout\Total;
use ShopBridge\Models\Checkout\PaymentProvider;
use ShopBridge\Models\Checkout\FulfillmentOption;
use ShopBridge\Models\Checkout\Link;

final class CheckoutSession
{
    private string $id;
    private string $status;
    private string $currency;
    /** @var LineItem[] */
    private array $lineItems;
    private ?Address $fulfillmentAddress;
    /** @var Total[] */
    private array $totals;
    /** @var FulfillmentOption[] */
    private array $fulfillmentOptions;
    private ?string $fulfillmentOptionId;
    /** @var Message[] */
    private array $messages;
    /** @var OrderReference|null */
    private ?OrderReference $order;
    private ?Buyer $buyer;
    private ?PaymentProvider $paymentProvider;
    /** @var Link[] */
    private array $links;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param string                         $currency
     * @param array<int, LineItem>           $lineItems
     * @param array<int, Total>              $totals
     * @param array<int, FulfillmentOption>  $fulfillmentOptions
     * @param array<int, Message>            $messages
     * @param array<int, Link>               $links
     * @param array<string, mixed>           $rawPayload
     */
    public function __construct(
        string $id,
        string $status,
        string $currency,
        array $lineItems,
        ?Address $fulfillmentAddress,
        array $totals,
        array $fulfillmentOptions,
        ?string $fulfillmentOptionId,
        array $messages,
        ?OrderReference $order,
        ?Buyer $buyer,
        ?PaymentProvider $paymentProvider,
        array $links,
        array $rawPayload
    ) {
        if ('' === trim($id)) {
            throw new ValidationException('id cannot be empty');
        }

        $allowedStatuses = [
            'not_ready_for_payment',
            'ready_for_payment',
            'completed',
            'canceled',
            'in_progress',
        ];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new ValidationException('status must be a valid ACP checkout session status');
        }

        if (!preg_match('/^[a-z]{3}$/i', $currency)) {
            throw new ValidationException('currency must be a 3-letter ISO 4217 code');
        }

        $this->id = $id;
        $this->status = $status;
        $this->currency = strtolower($currency);
        $this->lineItems = $lineItems;
        $this->fulfillmentAddress = $fulfillmentAddress;
        $this->totals = $totals;
        $this->fulfillmentOptions = $fulfillmentOptions;
        $this->fulfillmentOptionId = $fulfillmentOptionId;
        $this->messages = $messages;
        $this->order = $order;
        $this->buyer = $buyer;
        $this->paymentProvider = $paymentProvider;
        $this->links = $links;
        $this->rawPayload = $rawPayload;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getFulfillmentAddress(): ?Address
    {
        return $this->fulfillmentAddress;
    }

    /**
     * @return Total[]
     */
    public function getTotals(): array
    {
        return $this->totals;
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getOrder(): ?OrderReference
    {
        return $this->order;
    }

    public function getBuyer(): ?Buyer
    {
        return $this->buyer;
    }

    public function getPaymentProvider(): ?PaymentProvider
    {
        return $this->paymentProvider;
    }

    /**
     * @return FulfillmentOption[]
     */
    public function getFulfillmentOptions(): array
    {
        return $this->fulfillmentOptions;
    }

    public function getFulfillmentOptionId(): ?string
    {
        return $this->fulfillmentOptionId;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $address = null;
        if (isset($payload['fulfillment_address'])) {
            $addressData = (array) $payload['fulfillment_address'];
            $address = new Address(
                $addressData['name'] ?? '',
                $addressData['line_one'] ?? '',
                $addressData['line_two'] ?? null,
                $addressData['city'] ?? '',
                $addressData['state'] ?? '',
                $addressData['country'] ?? '',
                $addressData['postal_code'] ?? ''
            );
        }

        $lineItemsPayload = isset($payload['line_items']) && is_array($payload['line_items'])
            ? $payload['line_items']
            : [];

        $lineItems = [];
        foreach ($lineItemsPayload as $itemPayload) {
            if (is_array($itemPayload)) {
                $lineItems[] = LineItem::fromArray($itemPayload);
            }
        }

        $totals = [];
        if (isset($payload['totals']) && is_array($payload['totals'])) {
            foreach ($payload['totals'] as $totalPayload) {
                if (is_array($totalPayload)) {
                    $totals[] = Total::fromArray($totalPayload);
                }
            }
        }

        $messages = [];
        if (isset($payload['messages']) && is_array($payload['messages'])) {
            foreach ($payload['messages'] as $messagePayload) {
                if (is_array($messagePayload)) {
                    $messages[] = Message::fromArray($messagePayload);
                }
            }
        }

        $order = null;
        if (isset($payload['order']) && is_array($payload['order'])) {
            $order = OrderReference::fromArray($payload['order']);
        }

        $fulfillmentOptions = [];
        if (isset($payload['fulfillment_options']) && is_array($payload['fulfillment_options'])) {
            foreach ($payload['fulfillment_options'] as $optionPayload) {
                if (is_array($optionPayload)) {
                    $fulfillmentOptions[] = FulfillmentOption::fromArray($optionPayload);
                }
            }
        }

        $links = [];
        if (isset($payload['links']) && is_array($payload['links'])) {
            foreach ($payload['links'] as $linkPayload) {
                if (is_array($linkPayload)) {
                    $links[] = Link::fromArray($linkPayload);
                }
            }
        }

        $buyer = null;
        if (isset($payload['buyer']) && is_array($payload['buyer'])) {
            $buyer = Buyer::fromArray($payload['buyer']);
        }

        $paymentProvider = null;
        if (isset($payload['payment_provider']) && is_array($payload['payment_provider'])) {
            $paymentProvider = PaymentProvider::fromArray($payload['payment_provider']);
        }

        return new self(
            (string) ($payload['id'] ?? ''),
            (string) ($payload['status'] ?? ''),
            (string) ($payload['currency'] ?? ''),
            $lineItems,
            $address,
            $totals,
            $fulfillmentOptions,
            isset($payload['fulfillment_option_id']) ? (string) $payload['fulfillment_option_id'] : null,
            $messages,
            $order,
            $buyer,
            $paymentProvider,
            $links,
            $payload
        );
    }
}
