<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use ShopBridge\Contracts\SignatureGeneratorInterface;
use ShopBridge\Exceptions\Checkout\ActionRequiredException;
use ShopBridge\Exceptions\Checkout\IdempotencyConflictException;
use ShopBridge\Exceptions\Checkout\OutOfStockException;
use ShopBridge\Exceptions\Checkout\PaymentDeclinedException;
use ShopBridge\Exceptions\Checkout\ProcessingException;
use ShopBridge\Exceptions\Checkout\RateLimitException;
use ShopBridge\Exceptions\Checkout\RequestNotIdempotentException;
use ShopBridge\Exceptions\Checkout\ServiceUnavailableException;
use ShopBridge\Http\AcpHttpClient;
use ShopBridge\Models\Checkout\LineItem;
use ShopBridge\Models\Checkout\PaymentData;
use ShopBridge\Requests\Checkout\CheckoutSessionCompleteRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionCreateRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionItemRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionUpdateRequest;
use ShopBridge\Services\CheckoutService;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriInterface;

final class CheckoutServiceTest extends TestCase
{
    public function testCreateSessionReturnsCheckoutSession(): void
    {
        $payload = [
            'id' => 'cs_test',
            'status' => 'ready_for_payment',
            'currency' => 'usd',
            'line_items' => [
                [
                    'id' => 'line_item_1',
                    'item' => [
                        'id' => 'sku_1',
                        'quantity' => 1,
                    ],
                    'base_amount' => 1000,
                    'discount' => 0,
                    'subtotal' => 1000,
                    'tax' => 0,
                    'total' => 1000,
                ],
            ],
            'totals' => [
                ['type' => 'subtotal', 'display_text' => 'Subtotal', 'amount' => 1000],
                ['type' => 'total', 'display_text' => 'Total', 'amount' => 1000],
            ],
            'fulfillment_options' => [
                [
                    'type' => 'shipping',
                    'id' => 'ship_1',
                    'title' => 'Express',
                    'subtitle' => '1-2 days',
                    'carrier' => 'DHL',
                    'earliest_delivery_time' => '2025-01-01T00:00:00Z',
                    'latest_delivery_time' => '2025-01-02T00:00:00Z',
                    'subtotal' => 500,
                    'tax' => 50,
                    'total' => 550,
                ],
            ],
            'fulfillment_option_id' => 'ship_1',
            'messages' => [],
            'order' => [
                'id' => 'order_123',
                'checkout_session_id' => 'cs_test',
                'permalink_url' => 'https://example.com/orders/123',
            ],
            'buyer' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
            ],
            'payment_provider' => [
                'provider' => 'stripe',
                'supported_payment_methods' => ['card'],
            ],
            'links' => [
                [
                    'type' => 'terms_of_use',
                    'url' => 'https://example.com/terms',
                ],
            ],
            'fulfillment_address' => [
                'name' => 'John',
                'line_one' => '123 Main',
                'line_two' => null,
                'city' => 'San Francisco',
                'state' => 'CA',
                'country' => 'US',
                'postal_code' => '94105',
            ],
        ];

        $recorder = null;
        $client = $this->buildClient(new DummyResponse($payload), $recorder);
        $request = $this->createCheckoutRequest();

        $service = $this->createService($client);
        $session = $service->createSession($request);

        $this->assertSame('cs_test', $session->getId());
        $this->assertSame('ready_for_payment', $session->getStatus());
        $this->assertNotEmpty($session->getLineItems());
        $this->assertInstanceOf(LineItem::class, $session->getLineItems()[0]);
        $this->assertCount(2, $session->getTotals());
        $this->assertNotNull($session->getOrder());
        $this->assertCount(0, $session->getMessages());
        $this->assertSame('ship_1', $session->getFulfillmentOptionId());
        $this->assertCount(1, $session->getFulfillmentOptions());
        $buyer = $session->getBuyer();
        $this->assertNotNull($buyer);
        $this->assertSame('John', $buyer->getFirstName());
        $paymentProvider = $session->getPaymentProvider();
        $this->assertNotNull($paymentProvider);
        $this->assertSame('stripe', $paymentProvider->getProvider());
        $this->assertCount(1, $session->getLinks());

        $this->assertNotNull($recorder);
        $lastRequest = $recorder->lastRequest;
        $this->assertInstanceOf(RequestInterface::class, $lastRequest);
        $this->assertSame('/checkout_sessions', $lastRequest->getUri()->getPath());
        $this->assertSame('POST', $lastRequest->getMethod());
        $this->assertNotEmpty($lastRequest->getHeader('Signature'));
        $this->assertSame('en-US', $lastRequest->getHeaderLine('Accept-Language'));
        $this->assertSame('ShopBridge-PHP/' . \ShopBridge\Support\Version::string(), $lastRequest->getHeaderLine('User-Agent'));
        $body = json_decode((string) $lastRequest->getBody(), true);
        $this->assertSame($request->toArray(), $body);
    }

    public function testThrowsOutOfStockExceptionOn409(): void
    {
        $client = $this->buildClient(new DummyResponse([
            'type' => 'invalid_request',
            'code' => 'out_of_stock',
            'message' => 'Item unavailable',
            'param' => '$.line_items[0]',
        ], 409));

        $service = $this->createService($client);

        $this->expectException(OutOfStockException::class);
        $service->createSession($this->createCheckoutRequest());
    }

    public function testPaymentDeclinedExceptionMapping(): void
    {
        $client = $this->buildClient($this->errorResponse(402, [
            'type' => 'invalid_request',
            'code' => 'payment_declined',
            'message' => 'Card declined',
        ]));

        $service = $this->createService($client);

        $this->expectException(PaymentDeclinedException::class);
        $service->createSession($this->createCheckoutRequest());
    }

    public function testActionRequiredExceptionMapping(): void
    {
        $client = $this->buildClient($this->errorResponse(400, [
            'type' => 'invalid_request',
            'code' => 'requires_3ds',
            'message' => '3DS flow required',
        ]));

        $service = $this->createService($client);

        $this->expectException(ActionRequiredException::class);
        $service->createSession($this->createCheckoutRequest());
    }

    public function testIdempotencyConflictExceptionMapping(): void
    {
        $client = $this->buildClient($this->errorResponse(409, [
            'type' => 'invalid_request',
            'code' => 'idempotency_conflict',
            'message' => 'Conflicting parameters',
        ]));

        $service = $this->createService($client);

        $this->expectException(IdempotencyConflictException::class);
        $service->createSession($this->createCheckoutRequest());

    }

    public function testRateLimitExceptionMapping(): void
    {
        $client = $this->buildClient($this->errorResponse(429, [
            'type' => 'rate_limit_exceeded',
            'code' => 'rate_limit_exceeded',
            'message' => 'Slow down',
        ]));

        $service = $this->createService($client);

        $this->expectException(RateLimitException::class);
        $service->createSession($this->createCheckoutRequest());
    }

    public function testProcessingExceptionMapping(): void
    {
        $client = $this->buildClient($this->errorResponse(500, [
            'type' => 'processing_error',
            'code' => 'processing_error',
            'message' => 'Internal issue',
        ]));

        $service = $this->createService($client);

        $this->expectException(ProcessingException::class);
        $service->createSession($this->createCheckoutRequest());
    }

    public function testServiceUnavailableExceptionMapping(): void
    {
        $client = $this->buildClient($this->errorResponse(503, [
            'type' => 'service_unavailable',
            'code' => 'service_unavailable',
            'message' => 'Down for maintenance',
        ]));

        $service = $this->createService($client);

        $this->expectException(ServiceUnavailableException::class);
        $service->createSession($this->createCheckoutRequest());
    }

    public function testRequestNotIdempotentMapping(): void
    {
        $client = $this->buildClient($this->errorResponse(409, [
            'type' => 'request_not_idempotent',
            'code' => 'request_not_idempotent',
            'message' => 'Idempotency mismatch',
        ]));

        $service = $this->createService($client);

        $this->expectException(RequestNotIdempotentException::class);
        $service->createSession($this->createCheckoutRequest());
    }

    public function testUpdateSessionUsesRequestPayload(): void
    {
        $responsePayload = [
            'id' => 'cs_test',
            'status' => 'ready_for_payment',
            'currency' => 'usd',
            'line_items' => [],
            'totals' => [],
            'fulfillment_options' => [],
            'messages' => [],
            'links' => [],
        ];

        $recorder = null;
        $client = $this->buildClient($this->successResponse($responsePayload), $recorder);

        $service = $this->createService($client);
        $request = new CheckoutSessionUpdateRequest([
            new CheckoutSessionItemRequest('sku', 2),
        ]);

        $session = $service->updateSession('cs_test', $request);
        $this->assertSame('cs_test', $session->getId());

        $this->assertNotNull($recorder);
        $lastRequest = $recorder->lastRequest;
        $this->assertInstanceOf(RequestInterface::class, $lastRequest);
        $this->assertSame('/checkout_sessions/cs_test', $lastRequest->getUri()->getPath());
        $body = json_decode((string) $lastRequest->getBody(), true);
        $this->assertSame($request->toArray(), $body);
    }

    public function testCompleteSessionUsesPaymentData(): void
    {
        $responsePayload = [
            'id' => 'cs_test',
            'status' => 'completed',
            'currency' => 'usd',
            'line_items' => [],
            'totals' => [],
            'fulfillment_options' => [],
            'messages' => [],
            'links' => [],
            'order' => [
                'id' => 'ord_123',
                'checkout_session_id' => 'cs_test',
                'permalink_url' => 'https://example.com/orders/ord_123',
            ],
        ];

        $recorder = null;
        $client = $this->buildClient($this->successResponse($responsePayload), $recorder);

        $service = $this->createService($client);
        $request = new CheckoutSessionCompleteRequest(new PaymentData('spt_token', 'stripe'));

        $session = $service->completeSession('cs_test', $request);
        $this->assertSame('completed', $session->getStatus());
        $order = $session->getOrder();
        $this->assertNotNull($order);
        $this->assertSame('ord_123', $order->getId());

        $this->assertNotNull($recorder);
        $lastRequest = $recorder->lastRequest;
        $this->assertInstanceOf(RequestInterface::class, $lastRequest);
        $this->assertSame('/checkout_sessions/cs_test/complete', $lastRequest->getUri()->getPath());
        $body = json_decode((string) $lastRequest->getBody(), true);
        $this->assertSame($request->toArray(), $body);
    }

    private function createService(AcpHttpClient $client): CheckoutService
    {
        return new CheckoutService($client, new class implements SignatureGeneratorInterface {
            public function generate(string $method, string $path, ?array $payload, string $timestamp, string $requestId): string
            {
                return 'test-signature';
            }
        });
    }

    private function createCheckoutRequest(): CheckoutSessionCreateRequest
    {
        return new CheckoutSessionCreateRequest([
            new CheckoutSessionItemRequest('sku', 1),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function successResponse(array $payload): ResponseInterface
    {
        return new DummyResponse($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function errorResponse(int $status, array $error): ResponseInterface
    {
        return new DummyResponse($error, $status);
    }

    private function buildClient(ResponseInterface $response, ?RecordingHttpClient &$recorder = null): AcpHttpClient
    {
        $recorder = new RecordingHttpClient($response);

        return new AcpHttpClient(
            $recorder,
            new ServiceRequestFactory(),
            new ServiceStreamFactory(),
            'https://merchant.example.com',
            'sk_test'
        );
    }
}

final class RecordingHttpClient implements ClientInterface
{
    public ?RequestInterface $lastRequest = null;
    private ResponseInterface $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->lastRequest = $request;

        return $this->response;
    }
}

class DummyResponse implements ResponseInterface
{
    private array $payload;
    private int $status;

    public function __construct(array $payload, int $status = 200)
    {
        $this->payload = $payload;
        $this->status = $status;
    }

    public function getProtocolVersion(): string { return '1.1'; }
    public function withProtocolVersion(string $version): ResponseInterface { return $this; }
    public function getHeaders(): array { return []; }
    public function hasHeader(string $name): bool { return false; }
    public function getHeader(string $name): array { return []; }
    public function getHeaderLine(string $name): string { return ''; }
    public function withHeader(string $name, $value): ResponseInterface { return $this; }
    public function withAddedHeader(string $name, $value): ResponseInterface { return $this; }
    public function withoutHeader(string $name): ResponseInterface { return $this; }
    public function getBody(): StreamInterface { return new DummyReadStream(json_encode($this->payload) ?: '{}'); }
    public function withBody(StreamInterface $body): ResponseInterface { return $this; }
    public function getStatusCode(): int { return $this->status; }
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface { return $this; }
    public function getReasonPhrase(): string { return 'OK'; }
}

final class DummyReadStream implements StreamInterface
{
    private int $pointer = 0;
    private string $contents;

    public function __construct(string $contents)
    {
        $this->contents = $contents;
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

final class ServiceRequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        $uriString = (string) $uri;
        $parsed = parse_url($uriString) ?: [];

        $scheme = $parsed['scheme'] ?? '';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';

        return new ServiceRequest($method, new ServiceUri($scheme, $host, $path, $query));
    }
}

final class ServiceStreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return new DummyReadStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return new DummyReadStream((string) file_get_contents($filename));
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return new DummyReadStream(stream_get_contents($resource) ?: '');
    }
}

final class ServiceRequest implements RequestInterface
{
    private string $method;
    private ServiceUri $uri;
    /** @var array<string, string[]> */
    private array $headers = [];
    private ?StreamInterface $body = null;

    public function __construct(string $method, ServiceUri $uri)
    {
        $this->method = $method;
        $this->uri = $uri;
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): RequestInterface
    {
        $clone = clone $this;
        return $clone;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    public function getHeader(string $name): array
    {
        return $this->headers[strtolower($name)] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(',', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): RequestInterface
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = (array) $value;
        return $clone;
    }

    public function withAddedHeader(string $name, $value): RequestInterface
    {
        $clone = clone $this;
        $key = strtolower($name);
        $clone->headers[$key] = array_merge($clone->headers[$key] ?? [], (array) $value);
        return $clone;
    }

    public function withoutHeader(string $name): RequestInterface
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body ?? new DummyReadStream('');
    }

    public function withBody(StreamInterface $body): RequestInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function getRequestTarget(): string
    {
        $target = $this->uri->getPath();
        $query = $this->uri->getQuery();
        if ($query !== '') {
            $target .= '?' . $query;
        }

        return $target;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $clone = clone $this;
        $targetParts = explode('?', $requestTarget, 2);
        $path = $targetParts[0];
        $query = $targetParts[1] ?? '';
        $clone->uri = $clone->uri->withPath($path)->withQuery($query);
        return $clone;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod(string $method): RequestInterface
    {
        $clone = clone $this;
        $clone->method = $method;
        return $clone;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $clone = clone $this;
        $clone->uri = ServiceUri::fromUri($uri);
        return $clone;
    }
}

final class ServiceUri implements UriInterface
{
    private string $scheme;
    private string $host;
    private string $path;
    private string $query;
    private ?int $port;
    private string $userInfo;

    public function __construct(
        string $scheme,
        string $host,
        string $path,
        string $query,
        ?int $port = null,
        string $userInfo = ''
    ) {
        $this->scheme = $scheme;
        $this->host = $host;
        $this->path = $path;
        $this->query = $query;
        $this->port = $port;
        $this->userInfo = $userInfo;
    }

    public static function fromUri(UriInterface $uri): self
    {
        return new self(
            $uri->getScheme(),
            $uri->getHost(),
            $uri->getPath(),
            $uri->getQuery(),
            $uri->getPort(),
            $uri->getUserInfo()
        );
    }

    public function __toString(): string
    {
        $query = $this->query !== '' ? '?' . $this->query : '';

        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }
        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return ($this->scheme !== '' ? $this->scheme . '://' : '') . $authority . $this->path . $query;
    }

    public function getScheme(): string { return $this->scheme; }
    public function getAuthority(): string
    {
        $authority = $this->host;
        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }
        if ($this->port !== null) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }
    public function getUserInfo(): string { return $this->userInfo; }
    public function getHost(): string { return $this->host; }
    public function getPort(): ?int { return $this->port; }
    public function getPath(): string { return $this->path; }
    public function getQuery(): string { return $this->query; }
    public function getFragment(): string { return ''; }
    public function withScheme(string $scheme): UriInterface { $clone = clone $this; $clone->scheme = $scheme; return $clone; }
    public function withUserInfo(string $user, ?string $password = null): UriInterface { $clone = clone $this; $clone->userInfo = $password === null ? $user : $user . ':' . $password; return $clone; }
    public function withHost(string $host): UriInterface { $clone = clone $this; $clone->host = $host; return $clone; }
    public function withPort(?int $port): UriInterface { $clone = clone $this; $clone->port = $port; return $clone; }
    public function withPath(string $path): UriInterface { $clone = clone $this; $clone->path = $path; return $clone; }
    public function withQuery(string $query): UriInterface { $clone = clone $this; $clone->query = $query; return $clone; }
    public function withFragment(string $fragment): UriInterface { return $this; }
}
