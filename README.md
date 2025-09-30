# ShopBridge PHP SDK

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)](#requirements)
[![Composer Package](https://img.shields.io/badge/Packagist-shopbridge%2Fshopbridge--php-orange.svg)](https://packagist.org/packages/shopbridge/shopbridge-php)

ShopBridge PHP SDK helps merchants integrate with the [Agentic Commerce Protocol (ACP)](https://www.agenticcommerce.dev/). The library adheres to the official specifications published in the [agentic-commerce-protocol](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol) repository and remains compatible with PHP 7.4 and newer.

## Highlights

- Merchant checkout lifecycle support with rich DTOs and validation (`create`, `update`, `get`, `complete`, `cancel`).
- Webhook signature verification and event parsing tailored to ACP payloads.
- Product feed builders for ACP-compliant catalog exports (JSON, CSV, TSV, XML).
- Framework-agnostic, PSR-7/17/18 compatible design that runs on PHP 7.4 and newer.

## Requirements

- PHP 7.4+
- Composer 2
- PSR-18 HTTP client and PSR-17 factories (for example `guzzlehttp/guzzle` together with `guzzlehttp/psr7`)

## Installation

```bash
composer require shopbridge/shopbridge-php
```

## Quick start

```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use ShopBridge\Models\Checkout\PaymentData;
use ShopBridge\Requests\Checkout\CheckoutSessionCompleteRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionCreateRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionItemRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionUpdateRequest;
use ShopBridge\ShopBridge;
use ShopBridge\Support\HmacSignatureGenerator;

$httpClient = new GuzzleClient();
$requestFactory = new HttpFactory();
$streamFactory = new HttpFactory();
$signatureGenerator = new HmacSignatureGenerator('merchant-secret');

$shopBridge = new ShopBridge(
    $httpClient,
    $requestFactory,
    $streamFactory,
    'https://merchant.example.com',
    'sk_live_...',
    $signatureGenerator
);

// Create a checkout session
$createRequest = new CheckoutSessionCreateRequest([
    new CheckoutSessionItemRequest('sku_123', 1),
]);
$session = $shopBridge->checkout()->createSession($createRequest);

// Update the session
$updateRequest = new CheckoutSessionUpdateRequest([
    new CheckoutSessionItemRequest('sku_123', 2),
]);
$session = $shopBridge->checkout()->updateSession($session->getId(), $updateRequest);

// Complete the checkout with a payment token
$completeRequest = new CheckoutSessionCompleteRequest(
    new PaymentData('spt_abc123', 'stripe')
);
$session = $shopBridge->checkout()->completeSession($session->getId(), $completeRequest);
```

### Webhook handling

```php
use ShopBridge\Services\WebhookService;
use ShopBridge\Support\HmacSignatureValidator;

$validator = new HmacSignatureValidator('merchant-secret');
$webhookService = new WebhookService($validator);

try {
    $event = $webhookService->parseWebhook($payload, $signatureFromHeader, 'merchant-secret');
    // React to the event, for example $event->getData()->getStatus()
} catch (\ShopBridge\Exceptions\InvalidSignatureException $exception) {
    // Signature verification failed — reject the request
} catch (\ShopBridge\Exceptions\TransportException $exception) {
    // Payload is not valid JSON or is missing required ACP fields
}
```

### Product feed export

```php
use PDO;
use ShopBridge\Models\Common\Currency;
use ShopBridge\Models\Common\Money;
use ShopBridge\ProductFeed\CsvFormatter;
use ShopBridge\ProductFeed\Product;
use ShopBridge\ProductFeed\ProductFeedBuilder;

$pdo = new PDO('mysql:host=localhost;dbname=shop', 'user', 'pass');
$builder = new ProductFeedBuilder(null, new CsvFormatter());

$products = (function () use ($pdo): \Generator {
    $stmt = $pdo->query('SELECT * FROM products');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        yield new Product(
            $row['sku'],
            $row['title'],
            $row['description'],
            $row['link'],
            new Money((int) $row['price_minor_units'], new Currency($row['currency'])),
            (bool) $row['enable_search'],
            (bool) $row['enable_checkout'],
            $row['availability'],
            (int) $row['inventory_quantity'],
            $row['image_main'],
            json_decode($row['image_additional'], true) ?? []
        );
    }
})();

$csv = $builder->buildString($products);
file_put_contents('feed.csv', $csv);

// For very large catalogs, stream chunks instead of building the entire string in memory:
$stream = fopen('feed.csv', 'w');
foreach ($builder->build($products) as $chunk) {
    fwrite($stream, $chunk);
}
fclose($stream);
```

## Project development

We welcome contributions! Here’s how to get involved.

### Local setup

```bash
composer install
composer test
composer lint
```

- `composer test` runs PHPUnit.
- `composer lint` runs PHPStan (`phpstan.neon.dist`). Keep the report clean before opening a PR.

### Filing issues

Helpful reports include:

- What you expected to happen vs. what happened.
- Sample payloads/responses (anonymised), SDK version, PHP version, and `API-Version` header in use.

### Pull request checklist

1. Fork the repo and create a topic branch.
2. Add tests/docs that cover the change.
3. Run `composer test` and `composer lint` locally.
4. Ensure the code stays compatible with PHP 7.4 (no enums, union types, constructor property promotion, etc.).
5. Reference any related issues or ACP spec updates in the PR description.

### Updating the ACP API version

The default `API-Version` lives in `src/Http/AcpHttpClient.php`.

### Reference material

- ACP documentation: [agenticcommerce.dev](https://www.agenticcommerce.dev/)
- Official specs and examples: [agentic-commerce-protocol](https://github.com/agentic-commerce-protocol/agentic-commerce-protocol)

The SDK is under active development and not yet recommended for production workloads. If you run into trouble, open an issue and we’ll help out.
