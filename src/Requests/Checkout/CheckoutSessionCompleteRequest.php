<?php

declare(strict_types=1);

namespace ShopBridge\Requests\Checkout;

use ShopBridge\Models\Checkout\PaymentData;
use ShopBridge\Models\Common\Buyer;

final class CheckoutSessionCompleteRequest
{
    private PaymentData $paymentData;
    private ?Buyer $buyer;

    public function __construct(PaymentData $paymentData, ?Buyer $buyer = null)
    {
        $this->paymentData = $paymentData;
        $this->buyer = $buyer;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'payment_data' => $this->paymentData->toArray(),
        ];

        if ($this->buyer !== null) {
            $payload['buyer'] = $this->buyer->toArray();
        }

        return $payload;
    }
}

