<?php

namespace App\Exceptions;

use Exception;

class LoyverseApiException extends Exception
{
    protected $errorCode;
    protected $errorData;

    public function __construct(
        string $message,
        int $statusCode = 0,
        string $errorCode = 'UNKNOWN_ERROR',
        array $errorData = []
    ) {
        parent::__construct($message, $statusCode);

        $this->errorCode = $errorCode;
        $this->errorData = $errorData;
    }

    /**
     * Get the error code from the API response.
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the full error data from the API response.
     */
    public function getErrorData(): array
    {
        return $this->errorData;
    }

    /**
     * Check if this is a rate limit error.
     */
    public function isRateLimitError(): bool
    {
        return $this->getCode() === 429;
    }

    /**
     * Check if this is an authentication error.
     */
    public function isAuthenticationError(): bool
    {
        return $this->getCode() === 401;
    }

    /**
     * Check if this is a validation error.
     */
    public function isValidationError(): bool
    {
        return $this->getCode() === 400;
    }

    /**
     * Check if this is a server error.
     */
    public function isServerError(): bool
    {
        return $this->getCode() >= 500;
    }

    /**
     * Get retry delay in seconds (for rate limit errors).
     */
    public function getRetryAfter(): ?int
    {
        if (isset($this->errorData['retry_after'])) {
            return (int) $this->errorData['retry_after'];
        }

        // Default retry delay for rate limit errors
        if ($this->isRateLimitError()) {
            return 60;
        }

        return null;
    }
}
