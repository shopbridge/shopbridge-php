<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use ShopBridge\Exceptions\TransportException;
use ShopBridge\Http\AcpHttpClient;

final class AcpHttpClientTest extends TestCase
{
    public function testThrowsTransportExceptionWhenJsonEncodingFails(): void
    {
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');

        $httpClient = new AcpHttpClient(
            $client,
            new DummyRequestFactory(),
            new DummyStreamFactory(),
            'https://api.example.com',
            'sk_test'
        );

        $resource = fopen('php://temp', 'r');
        try {
            $this->expectException(TransportException::class);
            $httpClient->post('/checkout_sessions', ['invalid' => $resource]);
        } finally {
            fclose($resource);
        }
    }
}

final class DummyRequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new DummyRequest($method, (string) $uri);
    }
}

final class DummyStreamFactory implements StreamFactoryInterface
{
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
        return $this->body ?? new DummyStream('');
    }

    public function withBody(StreamInterface $body): RequestInterface
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function getRequestTarget(): string
    {
        return $this->uri;
    }

    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        $clone = clone $this;
        $clone->uri = $requestTarget;
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
        return new DummyUri($this->uri);
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $clone = clone $this;
        $clone->uri = (string) $uri;
        return $clone;
    }
}

final class DummyUri implements UriInterface
{
    private string $uri;

    public function __construct(string $uri)
    {
        $this->uri = $uri;
    }

    public function __toString(): string { return $this->uri; }
    public function getScheme(): string { return ''; }
    public function getAuthority(): string { return ''; }
    public function getUserInfo(): string { return ''; }
    public function getHost(): string { return ''; }
    public function getPort(): ?int { return null; }
    public function getPath(): string { return $this->uri; }
    public function getQuery(): string { return ''; }
    public function getFragment(): string { return ''; }
    public function withScheme(string $scheme): UriInterface { return $this; }
    public function withUserInfo(string $user, ?string $password = null): UriInterface { return $this; }
    public function withHost(string $host): UriInterface { return $this; }
    public function withPort(?int $port): UriInterface { return $this; }
    public function withPath(string $path): UriInterface { $clone = clone $this; $clone->uri = $path; return $clone; }
    public function withQuery(string $query): UriInterface { return $this; }
    public function withFragment(string $fragment): UriInterface { return $this; }
}

final class DummyStream implements StreamInterface
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
