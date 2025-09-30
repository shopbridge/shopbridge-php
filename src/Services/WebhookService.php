<?php

declare(strict_types=1);

namespace ShopBridge\Services;

use JsonException;
use ShopBridge\Contracts\SignatureValidatorInterface;
use ShopBridge\Exceptions\InvalidSignatureException;
use ShopBridge\Exceptions\TransportException;
use ShopBridge\Models\Webhook\WebhookEvent;

final class WebhookService
{
    private SignatureValidatorInterface $signatureValidator;

    public function __construct(SignatureValidatorInterface $signatureValidator)
    {
        $this->signatureValidator = $signatureValidator;
    }

    /**
     * @throws InvalidSignatureException
     * @throws TransportException
     */
    public function parseWebhook(string $payload, string $signature, string $secret): WebhookEvent
    {
        $this->signatureValidator->validate($payload, $signature, $secret);

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new TransportException('Failed to decode webhook payload.', 0, $exception);
        }

        return WebhookEvent::fromArray($decoded);
    }
}
