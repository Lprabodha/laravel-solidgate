<?php

namespace Lahiru\LaravelSolidGate\Responses;

use Lahiru\LaravelSolidGate\Support\ErrorMessageFormatter;

/**
 * Response wrapper for SolidGate API responses.
 */
readonly class SolidGateResponse
{
    public function __construct(
        public array $data,
        public int $statusCode,
        public array $headers = []
    ) {}

    /**
     * Check if the response indicates success.
     *
     * SolidGate may return HTTP 200 with an "error" object for validation failures.
     */
    public function isSuccessful(): bool
    {
        if ($this->statusCode < 200 || $this->statusCode >= 300) {
            return false;
        }

        return ! isset($this->data['error']);
    }

    /**
     * Check if the response contains an API-level error.
     */
    public function hasError(): bool
    {
        return isset($this->data['error']);
    }

    /**
     * Get the API error payload, if present.
     */
    public function getError(): ?array
    {
        return $this->data['error'] ?? null;
    }

    /**
     * Get a flattened human-readable API error message.
     */
    public function getErrorMessage(): ?string
    {
        if (! $this->hasError()) {
            return null;
        }

        return ErrorMessageFormatter::fromResponse($this->data);
    }

    /**
     * Get a value from the response data using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $k) {
            if (! is_array($value) || ! array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Get the response data as an array.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Get the response data as JSON.
     */
    public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES): string
    {
        return json_encode($this->data, $flags);
    }
}
