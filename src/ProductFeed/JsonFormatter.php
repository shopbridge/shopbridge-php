<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

final class JsonFormatter implements FormatterInterface
{
    public function start(): string
    {
        return '[';
    }

    public function formatItem(array $product, bool $isFirst): string
    {
        $separator = $isFirst ? '' : ',';
        return $separator . json_encode($product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function end(): string
    {
        return ']';
    }

    public function getContentType(): string
    {
        return 'application/json';
    }
}
