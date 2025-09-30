<?php

declare(strict_types=1);

namespace ShopBridge\ProductFeed;

use DateTimeImmutable;
use ShopBridge\Exceptions\ValidationException;
use ShopBridge\Models\Common\Money;

final class Product
{
    private string $id;
    private string $title;
    private string $description;
    private string $link;
    private Money $price;
    private bool $enableSearch;
    private bool $enableCheckout;
    /** @var array<string, mixed> */
    private array $attributes;
    private string $availability;
    private ?DateTimeImmutable $availabilityDate;
    private int $inventoryQuantity;
    private string $imageLink;
    /** @var string[] */
    private array $additionalImageLinks;
    private ?string $videoLink;
    private ?string $model3dLink;
    private ?string $brand;
    private ?string $gtin;
    private ?string $mpn;
    private ?ProductCompliance $compliance;

    /**
     * @param string[]               $additionalImageLinks
     * @param array<string, mixed>   $attributes
     */
    public function __construct(
        string $id,
        string $title,
        string $description,
        string $link,
        Money $price,
        bool $enableSearch,
        bool $enableCheckout,
        string $availability,
        int $inventoryQuantity,
        string $imageLink,
        array $additionalImageLinks = [],
        ?string $videoLink = null,
        ?string $model3dLink = null,
        ?string $brand = null,
        ?string $gtin = null,
        ?string $mpn = null,
        ?DateTimeImmutable $availabilityDate = null,
        ?ProductCompliance $compliance = null,
        array $attributes = []
    ) {
        if ('' === trim($id)) {
            throw new ValidationException('Product id cannot be empty');
        }

        if ('' === trim($title)) {
            throw new ValidationException('Product title cannot be empty');
        }

        if ('' === trim($link)) {
            throw new ValidationException('Product link cannot be empty');
        }

        $availability = strtolower($availability);
        $allowedAvailability = ['in_stock', 'out_of_stock', 'preorder'];
        if (!in_array($availability, $allowedAvailability, true)) {
            throw new ValidationException('availability must be in_stock, out_of_stock, or preorder');
        }

        if ($availability === 'preorder' && $availabilityDate === null) {
            throw new ValidationException('availability_date is required when availability is preorder');
        }

        if ($inventoryQuantity < 0) {
            throw new ValidationException('inventory quantity must be non-negative');
        }

        if ('' === trim($imageLink)) {
            throw new ValidationException('image_link cannot be empty');
        }

        $normalizedAdditionalImages = [];
        foreach ($additionalImageLinks as $extraImage) {
            if ('' === trim($extraImage)) {
                throw new ValidationException('additional_image_link entries cannot be empty');
            }
            $normalizedAdditionalImages[] = $extraImage;
        }

        if ($videoLink !== null && '' === trim($videoLink)) {
            throw new ValidationException('video_link cannot be empty string when provided');
        }

        if ($model3dLink !== null && '' === trim($model3dLink)) {
            throw new ValidationException('model_3d_link cannot be empty string when provided');
        }

        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->link = $link;
        $this->price = $price;
        $this->enableSearch = $enableSearch;
        $this->enableCheckout = $enableCheckout;
        $this->attributes = $attributes;
        $this->availability = $availability;
        $this->availabilityDate = $availabilityDate;
        $this->inventoryQuantity = $inventoryQuantity;
        $this->imageLink = $imageLink;
        $this->additionalImageLinks = $normalizedAdditionalImages;
        $this->videoLink = $videoLink;
        $this->model3dLink = $model3dLink;
        $this->brand = $brand !== null ? trim($brand) : null;
        $this->gtin = $gtin !== null ? trim($gtin) : null;
        $this->mpn = $mpn !== null ? trim($mpn) : null;
        $this->compliance = $compliance;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function isSearchEnabled(): bool
    {
        return $this->enableSearch;
    }

    public function isCheckoutEnabled(): bool
    {
        return $this->enableCheckout;
    }

    public function getAvailability(): string
    {
        return $this->availability;
    }

    public function getInventoryQuantity(): int
    {
        return $this->inventoryQuantity;
    }

    public function getImageLink(): string
    {
        return $this->imageLink;
    }

    /**
     * @return string[]
     */
    public function getAdditionalImageLinks(): array
    {
        return $this->additionalImageLinks;
    }

    public function getAvailabilityDate(): ?DateTimeImmutable
    {
        return $this->availabilityDate;
    }

    public function getVideoLink(): ?string
    {
        return $this->videoLink;
    }

    public function getModel3dLink(): ?string
    {
        return $this->model3dLink;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function getGtin(): ?string
    {
        return $this->gtin;
    }

    public function getMpn(): ?string
    {
        return $this->mpn;
    }

    public function getCompliance(): ?ProductCompliance
    {
        return $this->compliance;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdditionalAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $base = [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'price' => $this->price->toArray(),
            'enable_search' => $this->enableSearch,
            'enable_checkout' => $this->enableCheckout,
            'availability' => $this->availability,
            'inventory_quantity' => $this->inventoryQuantity,
            'image_link' => $this->imageLink,
        ];

        if ($this->availabilityDate !== null) {
            $base['availability_date'] = $this->availabilityDate->format(DateTimeImmutable::ATOM);
        }

        if ($this->additionalImageLinks !== []) {
            $base['additional_image_link'] = $this->additionalImageLinks;
        }

        if ($this->videoLink !== null) {
            $base['video_link'] = $this->videoLink;
        }

        if ($this->model3dLink !== null) {
            $base['model_3d_link'] = $this->model3dLink;
        }

        if ($this->brand !== null && $this->brand !== '') {
            $base['brand'] = $this->brand;
        }

        if ($this->gtin !== null && $this->gtin !== '') {
            $base['gtin'] = $this->gtin;
        }

        if ($this->mpn !== null && $this->mpn !== '') {
            $base['mpn'] = $this->mpn;
        }

        if ($this->compliance !== null) {
            $base['compliance'] = $this->compliance->toArray();
        }

        return array_merge($base, $this->attributes);
    }
}
