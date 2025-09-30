<?php

declare(strict_types=1);

namespace ShopBridge\Http;

use JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use ShopBridge\Exceptions\TransportException;

final class AcpHttpClient
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private string $baseUrl;
    private string $apiKey;
    private string $apiVersion;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        string $baseUrl,
        string $apiKey,
        string $apiVersion = '2025-09-29'
    ) {
        $this->httpClient = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiVersion = $apiVersion;
    }

    /**
     * @param array<string, string> $headers
     */
    public function get(string $path, array $headers = []): ResponseInterface
    {
        return $this->send('GET', $path, null, $headers);
    }

    /**
     * @param array<mixed> $payload
     * @param array<string, string> $headers
     */
    public function post(string $path, array $payload, array $headers = []): ResponseInterface
    {
        return $this->send('POST', $path, $payload, $headers);
    }

    /**
     * @param array<mixed>|null $payload
     * @param array<string, string> $headers
     */
    public function send(string $method, string $path, ?array $payload = null, array $headers = []): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, $this->baseUrl . $path)
            ->withHeader('Authorization', 'Bearer ' . $this->apiKey)
            ->withHeader('API-Version', $this->apiVersion)
            ->withHeader('Accept', 'application/json');

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($payload !== null) {
            try {
                $encoded = json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new TransportException('Failed to encode request payload to JSON.', 0, $exception);
            }

            $body = $this->streamFactory->createStream($encoded);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($body);
        }

        return $this->httpClient->sendRequest($request);
    }
}
