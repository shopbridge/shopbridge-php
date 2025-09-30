<?php

declare(strict_types=1);

namespace ShopBridge\Contracts;

use ShopBridge\Exceptions\InvalidSignatureException;

interface SignatureValidatorInterface
{
    /**
     * @throws InvalidSignatureException
     */
    public function validate(string $payload, string $signature, string $secret = ''): void;
}
