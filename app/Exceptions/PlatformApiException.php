<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when platform API operations fail
 * Used for Careem, Talabat, and other delivery platform APIs
 */
class PlatformApiException extends Exception
{
    protected string $platform;
    protected ?int $statusCode;

    /**
     * Create a new Platform API exception
     *
     * @param string $platform Platform name (e.g., 'Careem', 'Talabat')
     * @param string $message Error message
     * @param int|null $statusCode HTTP status code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(
        string $platform,
        string $message = '',
        ?int $statusCode = null,
        ?\Throwable $previous = null
    ) {
        $this->platform = $platform;
        $this->statusCode = $statusCode;

        $fullMessage = "[{$platform} API] {$message}";

        parent::__construct($fullMessage, $statusCode ?? 0, $previous);
    }

    /**
     * Get the platform name
     */
    public function getPlatform(): string
    {
        return $this->platform;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * Determine if this is a rate limit error
     */
    public function isRateLimitError(): bool
    {
        return $this->statusCode === 429;
    }

    /**
     * Determine if this is an authentication error
     */
    public function isAuthError(): bool
    {
        return in_array($this->statusCode, [401, 403]);
    }

    /**
     * Determine if this is a server error
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Determine if the request should be retried
     */
    public function isRetryable(): bool
    {
        return $this->isRateLimitError() || $this->isServerError();
    }
}
