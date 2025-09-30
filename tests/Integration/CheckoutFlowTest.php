<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use ShopBridge\Contracts\SignatureGeneratorInterface;
use ShopBridge\Http\AcpHttpClient;
use ShopBridge\Models\Checkout\PaymentData;
use ShopBridge\Requests\Checkout\CheckoutSessionCompleteRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionCreateRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionItemRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionUpdateRequest;
use ShopBridge\Services\CheckoutService;

final class CheckoutFlowTest extends TestCase
{
    public function testFullCheckoutFlow(): void
    {
        $responses = [
            '/checkout_sessions' => $this->jsonResponse(201, $this->sessionPayload('ready_for_payment')),
            '/checkout_sessions/cs_test' => $this->jsonResponse(200, $this->sessionPayload('ready_for_payment')),
            '/checkout_sessions/cs_test/complete' => $this->jsonResponse(200, $this->sessionPayload('completed')),
            '/checkout_sessions/cs_test/cancel' => $this->jsonResponse(200, $this->sessionPayload('canceled')),
        ];

        $client = new class ($responses) implements ClientInterface {
            /** @var array<string, ResponseInterface> */
            private array $responses;
            public RequestInterface $lastRequest;
            /** @var array<string, string> */
            public array $lastHeaders = [];

            public function __construct(array $responses)
            {
                $this->responses = $responses;
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->lastRequest = $request;
                foreach ($request->getHeaders() as $name => $values) {
                    $this->lastHeaders[$name] = $values[0] ?? '';
                }

                $uri = $request->getUri()->getPath();
                if (!isset($this->responses[$uri])) {
                    throw new \RuntimeException('Unexpected URI: ' . $uri);
                }

                return $this->responses[$uri];
            }
        };

        $requestFactory = new class implements RequestFactoryInterface {
            public function createRequest(string $method, $uri): RequestInterface
            {
                $uriString = (string) $uri;
                $parsed = parse_url($uriString);
                $path = $parsed['path'] ?? $uriString;
                if (isset($parsed['query']) && $parsed['query'] !== '') {
                    $path .= '?' . $parsed['query'];
                }

                return new DummyRequest($method, $path);
            }
        };

        $streamFactory = new class implements StreamFactoryInterface {
            public function createStream(string $content = ''): StreamInterface
            {
                return new DummyStream($content);
            }

            public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
            {
                return new DummyStream((string) file_get_contents($filename));
            }

            public function createStreamFromResource($resource): StreamInterface
            {
                return new DummyStream(stream_get_contents($resource) ?: '');
            }
        };

        $httpClient = new AcpHttpClient($client, $requestFactory, $streamFactory, 'https://merchant.example.com', 'sk_test');
        $signatureGenerator = new class implements SignatureGeneratorInterface {
            public function generate(string $method, string $path, ?array $payload, string $timestamp, string $requestId): string
            {
                return 'sig_' . md5($method . $path . $timestamp . $requestId);
            }
        };

        $service = new CheckoutService($httpClient, $signatureGenerator);

        $createRequest = $this->createRequest();
        $session = $service->createSession($createRequest);
        $this->assertSame('ready_for_payment', $session->getStatus());
        $this->assertSame('usd', $session->getCurrency());
        $this->assertArrayHasKey('signature', $client->lastHeaders);

        $updateRequest = $this->updateRequest();
        $session = $service->updateSession('cs_test', $updateRequest);
        $this->assertSame('ready_for_payment', $session->getStatus());

        $completeRequest = $this->completeRequest();
        $session = $service->completeSession('cs_test', $completeRequest);
        $this->assertSame('completed', $session->getStatus());
        $order = $session->getOrder();
        $this->assertNotNull($order);
        $this->assertSame('ord_123', $order->getId());

        $session = $service->cancelSession('cs_test');
        $this->assertSame('canceled', $session->getStatus());
    }

    /**
     * @return CheckoutSessionCreateRequest
     */
    private function createRequest(): CheckoutSessionCreateRequest
    {
        return new CheckoutSessionCreateRequest([
            new CheckoutSessionItemRequest('sku', 1),
        ]);
    }

    private function updateRequest(): CheckoutSessionUpdateRequest
    {
        return new CheckoutSessionUpdateRequest([
            new CheckoutSessionItemRequest('sku', 1),
        ]);
    }

    private function completeRequest(): CheckoutSessionCompleteRequest
    {
        return new CheckoutSessionCompleteRequest(new PaymentData('spt_token', 'stripe'));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(int $status, array $payload): ResponseInterface
    {
        return new class ($status, $payload) implements ResponseInterface {
            private int $status;
            /** @var array<string, mixed> */
            private array $payload;

            public function __construct(int $status, array $payload)
            {
                $this->status = $status;
                $this->payload = $payload;
            }

            public function getProtocolVersion(): string { return '1.1'; }
            public function withProtocolVersion(string $version): ResponseInterface { return $this; }
            public function getHeaders(): array { return ['Request-Id' => ['req_123']]; }
            public function hasHeader(string $name): bool { return strtolower($name) === 'request-id'; }
            public function getHeader(string $name): array { return $this->hasHeader($name) ? ['req_123'] : []; }
            public function getHeaderLine(string $name): string { return $this->hasHeader($name) ? 'req_123' : ''; }
            public function withHeader(string $name, $value): ResponseInterface { return $this; }
            public function withAddedHeader(string $name, $value): ResponseInterface { return $this; }
            public function withoutHeader(string $name): ResponseInterface { return $this; }
            public function getBody(): StreamInterface
            {
                return new DummyStream(json_encode($this->payload));
            }
            public function withBody(StreamInterface $body): ResponseInterface { return $this; }
            public function getStatusCode(): int { return $this->status; }
            public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface { return $this; }
            public function getReasonPhrase(): string { return 'OK'; }
        };
    }

    private function sessionPayload(string $status): array
    {
        $payload = [
            'id' => 'cs_test',
            'status' => $status,
            'currency' => 'usd',
            'line_items' => [
                ['id' => 'line_item_1', 'item' => ['id' => 'sku', 'quantity' => 1], 'base_amount' => 1000, 'discount' => 0, 'subtotal' => 1000, 'tax' => 0, 'total' => 1000],
            ],
            'totals' => [
                ['type' => 'subtotal', 'display_text' => 'Subtotal', 'amount' => 1000],
                ['type' => 'total', 'display_text' => 'Total', 'amount' => 1000],
            ],
            'fulfillment_options' => [],
            'messages' => [],
            'links' => [],
        ];

        if ($status === 'completed') {
            $payload['order'] = [
                'id' => 'ord_123',
                'checkout_session_id' => 'cs_test',
                'permalink_url' => 'https://merchant.example.com/orders/ord_123',
            ];
        }

        return $payload;
    }
}

final class DummyRequest implements RequestInterface
{
    private string $method;
    private string $uri;
    /** @var array<string, string[]> */
    private array $headers = [];
    private ?StreamInterface $body = null;

    public function __construct(string $method, string $uri)
    {
        $this->method = $method;
        $this->uri = $uri;
    }
    public function getProtocolVersion(): string { return '1.1'; }
    public function withProtocolVersion(string $version): RequestInterface { $clone = clone $this; return $clone; }
    public function getHeaders(): array { return $this->headers; }
    public function hasHeader(string $name): bool { return array_key_exists(strtolower($name), $this->headers); }
    public function getHeader(string $name): array { return $this->headers[strtolower($name)] ?? []; }
    public function getHeaderLine(string $name): string { return implode(',', $this->getHeader($name)); }
    public function withHeader(string $name, $value): RequestInterface { $clone = clone $this; $clone->headers[strtolower($name)] = (array) $value; return $clone; }
    public function withAddedHeader(string $name, $value): RequestInterface { $clone = clone $this; $key = strtolower($name); $clone->headers[$key] = array_merge($clone->headers[$key] ?? [], (array) $value); return $clone; }
    public function withoutHeader(string $name): RequestInterface { $clone = clone $this; unset($clone->headers[strtolower($name)]); return $clone; }
    public function getBody(): StreamInterface { return $this->body ?? new DummyStream(''); }
    public function withBody(StreamInterface $body): RequestInterface { $clone = clone $this; $clone->body = $body; return $clone; }
    public function getRequestTarget(): string { return $this->uri; }
    public function withRequestTarget(string $requestTarget): RequestInterface { $clone = clone $this; $clone->uri = $requestTarget; return $clone; }
    public function getMethod(): string { return $this->method; }
    public function withMethod(string $method): RequestInterface { $clone = clone $this; $clone->method = $method; return $clone; }
    public function getUri(): \Psr\Http\Message\UriInterface { return new DummyUri($this->uri); }
    public function withUri(\Psr\Http\Message\UriInterface $uri, bool $preserveHost = false): RequestInterface { $clone = clone $this; $clone->uri = (string) $uri; return $clone; }
}

final class DummyUri implements \Psr\Http\Message\UriInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }
    public function __toString(): string { return $this->path; }
    public function getScheme(): string { return 'https'; }
    public function getAuthority(): string { return ''; }
    public function getUserInfo(): string { return ''; }
    public function getHost(): string { return 'merchant.example.com'; }
    public function getPort(): ?int { return null; }
    public function getPath(): string { return $this->path; }
    public function getQuery(): string { return ''; }
    public function getFragment(): string { return ''; }
    public function withScheme(string $scheme): \Psr\Http\Message\UriInterface { return $this; }
    public function withUserInfo(string $user, ?string $password = null): \Psr\Http\Message\UriInterface { return $this; }
    public function withHost(string $host): \Psr\Http\Message\UriInterface { return $this; }
    public function withPort(?int $port): \Psr\Http\Message\UriInterface { return $this; }
    public function withPath(string $path): \Psr\Http\Message\UriInterface { $clone = clone $this; $clone->path = $path; return $clone; }
    public function withQuery(string $query): \Psr\Http\Message\UriInterface { return $this; }
    public function withFragment(string $fragment): \Psr\Http\Message\UriInterface { return $this; }
}

final class DummyStream implements StreamInterface
{
    private string $contents;
    private int $pointer;

    public function __construct(string $contents, int $pointer = 0)
    {
        $this->contents = $contents;
        $this->pointer = $pointer;
    }
    public function __toString(): string { return $this->contents; }
    public function close(): void {}
    public function detach()
    {
        return null;
    }
    public function getSize(): ?int { return strlen($this->contents); }
    public function tell(): int { return $this->pointer; }
    public function eof(): bool { return $this->pointer >= strlen($this->contents); }
    public function isSeekable(): bool { return true; }
    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        $length = strlen($this->contents);
        switch ($whence) {
            case SEEK_SET:
                $this->pointer = max(0, $offset);
                break;
            case SEEK_CUR:
                $this->pointer = max(0, $this->pointer + $offset);
                break;
            case SEEK_END:
                $this->pointer = max(0, $length + $offset);
                break;
        }
        if ($this->pointer > $length) {
            $this->pointer = $length;
        }
    }
    public function rewind(): void { $this->pointer = 0; }
    public function isWritable(): bool { return false; }
    public function write(string $string): int { return 0; }
    public function isReadable(): bool { return true; }
    public function read(int $length): string
    {
        $chunk = substr($this->contents, $this->pointer, $length);
        $this->pointer += strlen($chunk);

        return $chunk;
    }
    public function getContents(): string
    {
        $remaining = substr($this->contents, $this->pointer);
        $this->pointer = strlen($this->contents);

        return $remaining;
    }
    public function getMetadata(?string $key = null)
    {
        if ($key === null) {
            return [];
        }

        return null;
    }
}
