<?php

declare(strict_types=1);

namespace ShopBridge\Models\Common;

use ShopBridge\Exceptions\ValidationException;

final class Money
{
    private int $amount;
    private Currency $currency;

    public function __construct(int $amount, Currency $currency)
    {
        if ($amount < 0) {
            throw new ValidationException('amount must be greater than or equal to zero');
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => (string) $this->currency,
        ];
    }
}
