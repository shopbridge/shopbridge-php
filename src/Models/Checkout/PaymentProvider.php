<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class PaymentProvider
{
    private string $provider;
    /** @var string[] */
    private array $supportedMethods;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param string[]               $supportedMethods
     * @param array<string, mixed>   $rawPayload
     */
    private function __construct(string $provider, array $supportedMethods, array $rawPayload)
    {
        if ('' === trim($provider)) {
            throw new ValidationException('payment provider cannot be empty');
        }

        $provider = strtolower($provider);
        $allowedProviders = ['stripe'];
        if (!in_array($provider, $allowedProviders, true)) {
            throw new ValidationException('unsupported payment provider');
        }

        $allowedMethods = ['card'];
        foreach ($supportedMethods as $method) {
            if (!in_array($method, $allowedMethods, true)) {
                throw new ValidationException('unsupported payment method provided');
            }
        }

        if ($supportedMethods === []) {
            throw new ValidationException('supported_payment_methods must contain at least one method');
        }

        $this->provider = $provider;
        $this->supportedMethods = $supportedMethods;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $methods = [];
        if (isset($payload['supported_payment_methods']) && is_array($payload['supported_payment_methods'])) {
            foreach ($payload['supported_payment_methods'] as $method) {
                $methods[] = strtolower((string) $method);
            }
        }

        return new self((string) ($payload['provider'] ?? ''), $methods, $payload);
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @return string[]
     */
    public function getSupportedMethods(): array
    {
        return $this->supportedMethods;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
