<?php

declare(strict_types=1);

namespace ShopBridge\Models\Common;

use ShopBridge\Exceptions\ValidationException;

final class Buyer
{
    private string $firstName;
    private string $lastName;
    private string $email;
    private ?string $phoneNumber;

    public function __construct(string $firstName, string $lastName, string $email, ?string $phoneNumber = null)
    {
        $this->assertNotEmpty($firstName, 'firstName');
        $this->assertNotEmpty($lastName, 'lastName');
        $this->assertEmail($email);

        if ($phoneNumber !== null && '' === trim($phoneNumber)) {
            throw new ValidationException('phone number cannot be empty string when provided');
        }

        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
        ];

        if ($this->phoneNumber !== null) {
            $data['phone_number'] = $this->phoneNumber;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            (string) ($payload['first_name'] ?? ''),
            (string) ($payload['last_name'] ?? ''),
            (string) ($payload['email'] ?? ''),
            isset($payload['phone_number']) ? (string) $payload['phone_number'] : null
        );
    }

    private function assertNotEmpty(string $value, string $field): void
    {
        if ('' === trim($value)) {
            throw new ValidationException(sprintf('%s cannot be empty', $field));
        }
    }

    private function assertEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('email must be a valid email address');
        }
    }
}
