<?php

declare(strict_types=1);

namespace ShopBridge\Models\Checkout;

use ShopBridge\Exceptions\ValidationException;

final class Message
{
    private const ERROR_CODES = [
        'missing',
        'invalid',
        'out_of_stock',
        'payment_declined',
        'requires_sign_in',
        'requires_3ds',
    ];

    private string $type;
    private string $contentType;
    private string $content;
    private ?string $code;
    private ?string $param;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $rawPayload
     */
    private function __construct(string $type, string $contentType, string $content, ?string $code, ?string $param, array $rawPayload)
    {
        if (!in_array($type, ['info', 'error'], true)) {
            throw new ValidationException('message type must be info or error');
        }

        if (!in_array($contentType, ['plain', 'markdown'], true)) {
            throw new ValidationException('content_type must be plain or markdown');
        }

        if ($type === 'error') {
            if ($code === null || $code === '') {
                throw new ValidationException('error messages require a code');
            }

            if (!in_array($code, self::ERROR_CODES, true)) {
                throw new ValidationException('unsupported error code for message');
            }
        }

        if ($content === '') {
            throw new ValidationException('message content cannot be empty');
        }

        $this->type = $type;
        $this->contentType = $contentType;
        $this->content = $content;
        $this->code = $code;
        $this->param = $param;
        $this->rawPayload = $rawPayload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['type'] ?? ''),
            (string) ($payload['content_type'] ?? ''),
            (string) ($payload['content'] ?? ''),
            isset($payload['code']) ? (string) $payload['code'] : null,
            isset($payload['param']) ? (string) $payload['param'] : null,
            $payload
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getParam(): ?string
    {
        return $this->param;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
