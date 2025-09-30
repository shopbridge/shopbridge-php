<?php

declare(strict_types=1);

namespace ShopBridge\Models\Common;

use ShopBridge\Exceptions\ValidationException;

final class Currency
{
    private string $code;

    public function __construct(string $code)
    {
        $normalized = strtolower($code);

        if (!preg_match('/^[a-z]{3}$/', $normalized)) {
            throw new ValidationException('currency must be a 3-letter ISO 4217 code');
        }

        $this->code = $normalized;
    }

    public function __toString(): string
    {
        return $this->code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}
