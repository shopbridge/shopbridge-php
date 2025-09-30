<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Address;

final class PaymentData
{
    private string $token;
    private string $provider;
    private ?Address $billingAddress;

    public function __construct(string $token, string $provider, ?Address $billingAddress = null)
    {
        if ('' === trim($token)) {
            throw new ValidationException('payment token cannot be empty');
        }

        $normalizedProvider = strtolower($provider);
        $allowedProviders = ['stripe'];
        if (!in_array($normalizedProvider, $allowedProviders, true)) {
            throw new ValidationException('unsupported payment provider');
        }

        $this->token = $token;
        $this->provider = $normalizedProvider;
        $this->billingAddress = $billingAddress;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'token' => $this->token,
            'provider' => $this->provider,
        ];

        if ($this->billingAddress !== null) {
            $payload['billing_address'] = $this->billingAddress->toArray();
        }

        return $payload;
    }
}

