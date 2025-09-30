<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

use DateTimeInterface;

final class XmlFormatter implements FormatterInterface
{
    public function start(): string
    {
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<products>\n";
    }

    public function formatItem(array $product, bool $isFirst): string
    {
        $xml = '  <product>' . "\n";

        foreach ($product as $key => $value) {
            $xml .= $this->formatElement((string) $key, $value, 2);
        }

        $xml .= '  </product>' . "\n";

        return $xml;
    }

    public function end(): string
    {
        return '</products>' . "\n";
    }

    public function getContentType(): string
    {
        return 'application/xml';
    }

    /**
     * @param mixed $value
     */
    private function formatElement(string $key, $value, int $depth): string
    {
        $tag = $this->sanitizeTag($key);
        $indent = str_repeat('  ', $depth);

        if (is_array($value)) {
            if ($this->isSequentialArray($value)) {
                $xml = '';
                foreach ($value as $item) {
                    $xml .= $this->formatElement($key, $item, $depth);
                }

                return $xml;
            }

            $xml = $indent . '<' . $tag . '>' . "\n";
            foreach ($value as $childKey => $childValue) {
                $xml .= $this->formatElement((string) $childKey, $childValue, $depth + 1);
            }

            return $xml . $indent . '</' . $tag . '>' . "\n";
        }

        if ($value instanceof DateTimeInterface) {
            $value = $value->format(DateTimeInterface::ATOM);
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        $escaped = htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');

        return $indent . '<' . $tag . '>' . $escaped . '</' . $tag . '>' . "\n";
    }

    private function sanitizeTag(string $key): string
    {
        $tag = preg_replace('/[^A-Za-z0-9_\-:]/', '_', $key) ?: 'field';

        if (preg_match('/^[0-9]/', $tag)) {
            $tag = '_' . $tag;
        }

        return $tag;
    }

    /**
     * @param array<int|string, mixed> $value
     */
    private function isSequentialArray(array $value): bool
    {
        $expectedIndex = 0;
        foreach ($value as $key => $_) {
            if ($key !== $expectedIndex) {
                return false;
            }
            $expectedIndex++;
        }

        return true;
    }
}

