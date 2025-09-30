<?php

declare(strict_types=1);

namespace ShopBridge;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ShopBridge\Contracts\SignatureGeneratorInterface;
use ShopBridge\Http\AcpHttpClient;
use ShopBridge\Services\CheckoutService;

final class ShopBridge
{
    private CheckoutService $checkoutService;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        string $baseUrl,
        string $apiKey,
        SignatureGeneratorInterface $signatureGenerator
    ) {
        $acpClient = new AcpHttpClient(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $baseUrl,
            $apiKey
        );

        $this->checkoutService = new CheckoutService($acpClient, $signatureGenerator);
    }

    public function checkout(): CheckoutService
    {
        return $this->checkoutService;
    }
}
