<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

final class ProductFeedBuilder
{
    private ProductSerializer $serializer;
    private FormatterInterface $formatter;

    public function __construct(?ProductSerializer $serializer = null, ?FormatterInterface $formatter = null)
    {
        $this->serializer = $serializer ?? new ProductSerializer();
        $this->formatter = $formatter ?? new JsonFormatter();
    }

    /**
     * @param iterable<Product> $products
     * @return \Generator<string>
     */
    public function build(iterable $products): \Generator
    {
        yield $this->formatter->start();

        $first = true;
        foreach ($products as $product) {
            $serialized = $this->serializer->serialize($product);
            yield $this->formatter->formatItem($serialized, $first);
            $first = false;
        }

        yield $this->formatter->end();
    }

    /**
     * @param iterable<Product> $products
     */
    public function buildString(iterable $products): string
    {
        return implode('', iterator_to_array($this->build($products)));
    }
}
