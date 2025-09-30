<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

class CsvFormatter implements FormatterInterface
{
    private const FIELD_ORDER = [
        'id',
        'title',
        'description',
        'link',
        'image_link',
        'additional_image_link',
        'availability',
        'availability_date',
        'inventory_quantity',
        'price',
        'brand',
        'gtin',
        'mpn',
        'enable_search',
        'enable_checkout',
    ];

    /** @var list<string> */
    private array $fieldOrder;
    private bool $headerEmitted = false;
    private string $delimiter;
    private string $contentType;

    public function __construct(string $delimiter = ",", string $contentType = 'text/csv')
    {
        $this->delimiter = $delimiter;
        $this->contentType = $contentType;
        $this->fieldOrder = self::FIELD_ORDER;
    }

    public function start(): string
    {
        return '';
    }

    public function formatItem(array $product, bool $isFirst): string
    {
        if (!$this->headerEmitted) {
            $this->extendFieldOrder($product);
            $this->headerEmitted = true;

            return $this->formatRow($this->fieldOrder) . $this->formatRow($this->mapRow($product));
        }

        return $this->formatRow($this->mapRow($product));
    }

    public function end(): string
    {
        return '';
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param list<string> $values
     */
    private function formatRow(array $values): string
    {
        $encoded = array_map(fn (string $value): string => $this->escapeField($value), $values);

        return implode($this->delimiter, $encoded) . "\n";
    }

    private function escapeField(string $value): string
    {
        if (strpos($value, '"') !== false || strpos($value, $this->delimiter) !== false || strpos($value, "\n") !== false) {
            $escaped = str_replace('"', '""', $value);

            return '"' . $escaped . '"';
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $product
     */
    private function normalizeField(array $product, string $field): string
    {
        $value = $product[$field] ?? '';

        if ($field === 'price' && is_array($value)) {
            return $this->formatMoney($value);
        }

        if (is_array($value)) {
            return implode('|', array_map('strval', $value));
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    /**
     * @param array{amount?: int, currency?: string} $money
     */
    private function formatMoney(array $money): string
    {
        $amount = $money['amount'] ?? 0;
        $currency = $money['currency'] ?? '';

        return $amount . ' ' . $currency;
    }

    /**
     * @param array<string, mixed> $product
     */
    /**
     * @param array<string, mixed> $product
     */
    private function extendFieldOrder(array $product): void
    {
        foreach (array_keys($product) as $key) {
            if (!in_array($key, $this->fieldOrder, true)) {
                $this->fieldOrder[] = $key;
            }
        }
    }

    /**
     * @param array<string, mixed> $product
     * @return string[]
     */
    /**
     * @param array<string, mixed> $product
     * @return list<string>
     */
    private function mapRow(array $product): array
    {
        $row = [];
        foreach ($this->fieldOrder as $field) {
            $row[] = $this->normalizeField($product, $field);
        }

        return $row;
    }
}
