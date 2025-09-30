<?php

declare(strict_types=1);

namespace ShopBridge\Services;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use ShopBridge\Contracts\SignatureGeneratorInterface;
use ShopBridge\Exceptions\Checkout\ActionRequiredException;
use ShopBridge\Exceptions\Checkout\CheckoutException;
use ShopBridge\Exceptions\Checkout\CheckoutNotFoundException;
use ShopBridge\Exceptions\Checkout\InvalidRequestException;
use ShopBridge\Exceptions\Checkout\InvalidCheckoutStateException;
use ShopBridge\Exceptions\Checkout\IdempotencyConflictException;
use ShopBridge\Exceptions\Checkout\OutOfStockException;
use ShopBridge\Exceptions\Checkout\PaymentDeclinedException;
use ShopBridge\Exceptions\Checkout\ProcessingException;
use ShopBridge\Exceptions\Checkout\RateLimitException;
use ShopBridge\Exceptions\Checkout\RequestNotIdempotentException;
use ShopBridge\Exceptions\Checkout\ServiceUnavailableException;
use ShopBridge\Exceptions\TransportException;
use ShopBridge\Http\AcpHttpClient;
use ShopBridge\Models\Checkout\CheckoutSession;
use ShopBridge\Requests\Checkout\CheckoutSessionCompleteRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionCreateRequest;
use ShopBridge\Requests\Checkout\CheckoutSessionUpdateRequest;
use ShopBridge\Support\IdGenerator;
use ShopBridge\Support\TimestampProvider;

final class CheckoutService
{
    private AcpHttpClient $client;
    private SignatureGeneratorInterface $signatureGenerator;
    private IdGenerator $idGenerator;
    private TimestampProvider $clock;
    private const DEFAULT_ACCEPT_LANGUAGE = 'en-US';

    public function __construct(
        AcpHttpClient $client,
        SignatureGeneratorInterface $signatureGenerator,
        ?IdGenerator $idGenerator = null,
        ?TimestampProvider $clock = null
    ) {
        $this->client = $client;
        $this->signatureGenerator = $signatureGenerator;
        $this->idGenerator = $idGenerator ?? new IdGenerator();
        $this->clock = $clock ?? new TimestampProvider();
    }

    /**
     * @param array<string, mixed> $context
     */
    public function createSession(CheckoutSessionCreateRequest $request, array $context = []): CheckoutSession
    {
        $payload = $request->toArray();
        $headers = $this->buildHeaders('POST', '/checkout_sessions', $payload, $context);
        $response = $this->client->post('/checkout_sessions', $payload, $headers);

        return CheckoutSession::fromArray($this->decodeResponse($response));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function updateSession(string $sessionId, CheckoutSessionUpdateRequest $request, array $context = []): CheckoutSession
    {
        $payload = $request->toArray();
        $path = '/checkout_sessions/' . $sessionId;
        $headers = $this->buildHeaders('POST', $path, $payload, $context);
        $response = $this->client->post($path, $payload, $headers);

        return CheckoutSession::fromArray($this->decodeResponse($response));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function getSession(string $sessionId, array $context = []): CheckoutSession
    {
        $path = '/checkout_sessions/' . $sessionId;
        $headers = $this->buildHeaders('GET', $path, null, $context);
        $response = $this->client->get($path, $headers);

        return CheckoutSession::fromArray($this->decodeResponse($response));
    }

    /**
     * @param array<string, mixed> $context
     */
    /**
     * @param array<string, mixed> $context
     */
    public function completeSession(string $sessionId, CheckoutSessionCompleteRequest $request, array $context = []): CheckoutSession
    {
        $payload = $request->toArray();
        $path = '/checkout_sessions/' . $sessionId . '/complete';
        $headers = $this->buildHeaders('POST', $path, $payload, $context);
        $response = $this->client->post($path, $payload, $headers);

        return CheckoutSession::fromArray($this->decodeResponse($response));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function cancelSession(string $sessionId, array $context = []): CheckoutSession
    {
        $path = '/checkout_sessions/' . $sessionId . '/cancel';
        $headers = $this->buildHeaders('POST', $path, [], $context);
        $response = $this->client->post($path, [], $headers);

        return CheckoutSession::fromArray($this->decodeResponse($response));
    }

    /**
     * @throws CheckoutException
     * @return array<string, mixed>
     */
    private function decodeResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        try {
            /** @var array<string, mixed>|null $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new TransportException('Failed to decode response payload.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new TransportException('Unexpected API response payload.');
        }

        $status = $response->getStatusCode();
        if ($status >= 400) {
            throw $this->mapError($response, $decoded);
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, mixed>      $context
     * @return array<string, string>
     */
    private function buildHeaders(string $method, string $path, ?array $payload, array $context): array
    {
        $timestamp = $context['timestamp'] ?? $this->clock->now();
        $requestId = $context['request_id'] ?? $this->idGenerator->generate();
        $idempotencyKey = $context['idempotency_key'] ?? ($method === 'GET' ? null : $this->idGenerator->generate());

        $headers = [
            'Request-Id' => $requestId,
            'Timestamp' => $timestamp,
        ];

        if ($idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $headers['Accept-Language'] = $context['accept_language'] ?? self::DEFAULT_ACCEPT_LANGUAGE;
        $headers['User-Agent'] = $context['user_agent'] ?? ('ShopBridge-PHP/' . \ShopBridge\Support\Version::string());

        $headers['Signature'] = $this->signatureGenerator->generate($method, $path, $payload, $timestamp, $requestId);

        if (isset($context['headers']) && is_array($context['headers'])) {
            foreach ($context['headers'] as $name => $value) {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function mapError(ResponseInterface $response, array $decoded): CheckoutException
    {
        $status = $response->getStatusCode();
        $requestId = $response->getHeaderLine('Request-Id') ?: null;

        $errorPayload = $decoded;

        $message = isset($errorPayload['message']) ? (string) $errorPayload['message'] : 'Unhandled error response.';
        $errorType = isset($errorPayload['type']) ? (string) $errorPayload['type'] : null;
        $errorCode = isset($errorPayload['code']) ? (string) $errorPayload['code'] : null;
        $param = isset($errorPayload['param']) ? (string) $errorPayload['param'] : null;

        switch ($errorCode) {
            case 'out_of_stock':
                return new OutOfStockException($message, $status, $errorType, $errorCode, $param, $requestId);
            case 'payment_declined':
                return new PaymentDeclinedException($message, $status, $errorType, $errorCode, $param, $requestId);
            case 'requires_sign_in':
            case 'requires_3ds':
                return new ActionRequiredException($message, $status, $errorType, $errorCode, $param, $requestId);
            case 'idempotency_conflict':
                return new IdempotencyConflictException($message, $status, $errorType, $errorCode, $param, $requestId);
            case 'invalid_checkout_state':
                return new InvalidCheckoutStateException($message, $status, $errorType, $errorCode, $param, $requestId);
            default:
                switch ($errorType) {
                    case 'rate_limit_exceeded':
                        return new RateLimitException($message, $status, $errorType, $errorCode, $param, $requestId);
                    case 'processing_error':
                        return new ProcessingException($message, $status, $errorType, $errorCode, $param, $requestId);
                    case 'service_unavailable':
                        return new ServiceUnavailableException($message, $status, $errorType, $errorCode, $param, $requestId);
                    case 'request_not_idempotent':
                        return new RequestNotIdempotentException($message, $status, $errorType, $errorCode, $param, $requestId);
                    default:
                        return $this->mapByStatus($status, $message, $errorType, $errorCode, $param, $requestId);
                }
        }
    }

    private function mapByStatus(
        int $status,
        string $message,
        ?string $errorType,
        ?string $errorCode,
        ?string $param,
        ?string $requestId
    ): CheckoutException {
        switch ($status) {
            case 429:
                return new RateLimitException($message, $status, $errorType, $errorCode, $param, $requestId);
            case 500:
                return new ProcessingException($message, $status, $errorType, $errorCode, $param, $requestId);
            case 503:
                return new ServiceUnavailableException($message, $status, $errorType, $errorCode, $param, $requestId);
            case 404:
                return new CheckoutNotFoundException($message, $status, $errorType, $errorCode, $param, $requestId);
            default:
                return new InvalidRequestException($message, $status, $errorType, $errorCode, $param, $requestId);
        }
    }
}
