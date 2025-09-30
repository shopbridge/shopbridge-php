<?php

declare(strict_types=1);

namespace ShopBridge\Support;

use JsonException;
use ShopBridge\Exceptions\TransportException;

final class CanonicalJson
{
    private const ENCODE_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION;

    /**
     * @param mixed $data
     */
    public static function encode($data): string
    {
        $normalized = self::normalize($data);

        try {
            $encoded = json_encode($normalized, JSON_THROW_ON_ERROR | self::ENCODE_FLAGS);
        } catch (JsonException $exception) {
            throw new TransportException('Failed to encode canonical JSON.', 0, $exception);
        }

        return $encoded;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function normalize($value)
    {
        if (is_array($value)) {
            if (self::isAssociative($value)) {
                $normalized = [];
                $keys = array_keys($value);
                sort($keys, SORT_STRING);
                foreach ($keys as $key) {
                    $normalized[$key] = self::normalize($value[$key]);
                }

                return $normalized;
            }

            $normalized = [];
            foreach ($value as $item) {
                $normalized[] = self::normalize($item);
            }

            return $normalized;
        }

        if ($value instanceof \JsonSerializable) {
            return self::normalize($value->jsonSerialize());
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        return $value;
    }

    /**
     * @param array<mixed> $value
     */
    private static function isAssociative(array $value): bool
    {
        $expectedIndex = 0;
        foreach ($value as $key => $_) {
            if ($key !== $expectedIndex) {
                return true;
            }

            $expectedIndex++;
        }

        return false;
    }
}
