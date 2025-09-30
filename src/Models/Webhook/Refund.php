<?php

declare(strict_types=1);

namespace ShopBridge\Models\Webhook;

use ShopBridge\Exceptions\ValidationException;

final class Refund
{
    private string $type;
    private int $amount;
    /** @var array<string, mixed> */
    private array $rawPayload;

    /**
     * @param array<string, mixed> $payload
     */
    private function __construct(string $type, int $amount, array $payload)
    {
        if (!in_array($type, ['store_credit', 'original_payment'], true)) {
            throw new ValidationException('refund type must be store_credit or original_payment');
        }

        if ($amount < 0) {
            throw new ValidationException('refund amount must be non-negative');
        }

        $this->type = $type;
        $this->amount = $amount;
        $this->rawPayload = $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['type'] ?? ''),
            isset($payload['amount']) ? (int) $payload['amount'] : -1,
            $payload
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawPayload;
    }
}
