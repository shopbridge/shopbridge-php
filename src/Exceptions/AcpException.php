<?php

declare(strict_types=1);

namespace ShopBridge\Exceptions;

use RuntimeException;

class AcpException extends RuntimeException
{
    private int $statusCode;
    private ?string $errorType;
    private ?string $errorCode;
    private ?string $param;
    private ?string $requestId;

    public function __construct(
        string $message,
        int $statusCode,
        ?string $errorType = null,
        ?string $errorCode = null,
        ?string $param = null,
        ?string $requestId = null,
        ?\Throwable $previous = null
    ) {
        $this->statusCode = $statusCode;
        $this->errorType = $errorType;
        $this->errorCode = $errorCode;
        $this->param = $param;
        $this->requestId = $requestId;
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorType(): ?string
    {
        return $this->errorType;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getParam(): ?string
    {
        return $this->param;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }
}
