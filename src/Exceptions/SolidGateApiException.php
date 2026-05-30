<?php

namespace Lahiru\LaravelSolidGate\Exceptions;

use Throwable;

/**
 * Exception thrown when SolidGate API returns an error response.
 */
class SolidGateApiException extends SolidGateException
{
    public function __construct(
        string $message = '',
        protected ?array $response = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the API response data.
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }
}
