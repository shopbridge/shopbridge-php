<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

final class ProductSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(Product $product): array
    {
        return $product->toArray();
    }
}
