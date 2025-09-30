<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

use ShopBridge\Exceptions\ValidationException;

final class ProductCompliance
{
    private ?string $warning;
    private ?string $warningUrl;
    private ?int $ageRestriction;

    public function __construct(?string $warning = null, ?string $warningUrl = null, ?int $ageRestriction = null)
    {
        if ($warning === null && $warningUrl === null && $ageRestriction === null) {
            throw new ValidationException('compliance requires at least one field');
        }

        if ($warning !== null && '' === trim($warning)) {
            throw new ValidationException('warning cannot be empty string');
        }

        if ($warningUrl !== null && '' === trim($warningUrl)) {
            throw new ValidationException('warning_url cannot be empty string');
        }

        if ($ageRestriction !== null && $ageRestriction <= 0) {
            throw new ValidationException('age_restriction must be positive integer');
        }

        $this->warning = $warning;
        $this->warningUrl = $warningUrl;
        $this->ageRestriction = $ageRestriction;
    }

    public function getWarning(): ?string
    {
        return $this->warning;
    }

    public function getWarningUrl(): ?string
    {
        return $this->warningUrl;
    }

    public function getAgeRestriction(): ?int
    {
        return $this->ageRestriction;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->warning !== null) {
            $data['warning'] = $this->warning;
        }

        if ($this->warningUrl !== null) {
            $data['warning_url'] = $this->warningUrl;
        }

        if ($this->ageRestriction !== null) {
            $data['age_restriction'] = $this->ageRestriction;
        }

        return $data;
    }
}

