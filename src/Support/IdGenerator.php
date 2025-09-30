<?php

declare(strict_types=1);

namespace ShopBridge\Support;

final class IdGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
