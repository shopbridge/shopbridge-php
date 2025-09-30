<?php

declare(strict_types=1);

namespace ShopBridge\Services;

final class WebhookResponse
{
    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function acknowledge(?string $requestId = null): array
    {
        $body = ['received' => true];
        if ($requestId !== null) {
            $body['request_id'] = $requestId;
        }

        return self::response(200, $body);
    }

    /**
     * @param array<string, mixed> $error
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    public static function error(int $status, array $error, ?string $requestId = null): array
    {
        $body = ['error' => $error];
        if ($requestId !== null) {
            $body['request_id'] = $requestId;
        }

        return self::response($status, $body);
    }

    /**
     * @param array<string, mixed> $body
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private static function response(int $status, array $body): array
    {
        try {
            $encoded = json_encode($body, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            // Should never happen with scalar data, but surface a safe fallback message.
            $encoded = json_encode(['error' => ['message' => 'Invalid webhook response body']], JSON_THROW_ON_ERROR);
        }

        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $encoded,
        ];
    }
}
