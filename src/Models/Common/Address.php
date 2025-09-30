<?php

declare(strict_types=1);

namespace ShopBridge\Models\Common;

use ShopBridge\Exceptions\ValidationException;

final class Address
{
    private string $name;
    private string $lineOne;
    private ?string $lineTwo;
    private string $city;
    private string $state;
    private string $country;
    private string $postalCode;

    public function __construct(
        string $name,
        string $lineOne,
        ?string $lineTwo,
        string $city,
        string $state,
        string $country,
        string $postalCode
    ) {
        $this->assertNotEmpty($name, 'name');
        $this->assertMaxLength($name, 256, 'name');
        $this->assertNotEmpty($lineOne, 'lineOne');
        $this->assertMaxLength($lineOne, 60, 'lineOne');
        if ($lineTwo !== null) {
            $this->assertMaxLength($lineTwo, 60, 'lineTwo');
        }
        $this->assertNotEmpty($city, 'city');
        $this->assertNotEmpty($country, 'country');
        $this->assertNotEmpty($state, 'state');
        $this->assertCountryCode($country);
        $this->assertNotEmpty($postalCode, 'postalCode');

        $this->name = $name;
        $this->lineOne = $lineOne;
        $this->lineTwo = $lineTwo;
        $this->city = $city;
        $this->state = strtoupper($state);
        $this->country = strtoupper($country);
        $this->postalCode = $postalCode;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLineOne(): string
    {
        return $this->lineOne;
    }

    public function getLineTwo(): ?string
    {
        return $this->lineTwo;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'line_one' => $this->lineOne,
            'city' => $this->city,
            'country' => $this->country,
            'postal_code' => $this->postalCode,
            'state' => $this->state,
        ];

        if ($this->lineTwo !== null) {
            $data['line_two'] = $this->lineTwo;
        }

        return $data;
    }

    private function assertNotEmpty(string $value, string $field): void
    {
        if ('' === trim($value)) {
            throw new ValidationException(sprintf('%s cannot be empty', $field));
        }
    }

    private function assertMaxLength(string $value, int $max, string $field): void
    {
        if (mb_strlen($value) > $max) {
            throw new ValidationException(sprintf('%s must not exceed %d characters', $field, $max));
        }
    }

    private function assertCountryCode(string $country): void
    {
        if (!preg_match('/^[A-Z]{2}$/i', $country)) {
            throw new ValidationException('country must be a valid ISO 3166-1 alpha-2 code');
        }
    }
}
