<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

interface FormatterInterface
{
    public function start(): string;

    /**
     * @param array<string, mixed> $product
     */
    public function formatItem(array $product, bool $isFirst): string;

    public function end(): string;

    public function getContentType(): string;
}
