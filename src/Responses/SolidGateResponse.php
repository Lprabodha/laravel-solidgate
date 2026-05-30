<?php

namespace Lahiru\LaravelSolidGate\Responses;

/**
 * Response wrapper for SolidGate API responses.
 */
readonly class SolidGateResponse
{
    public function __construct(
        public array $data,
        public int $statusCode,
        public array $headers = []
    ) {
    }

    /**
     * Check if the response indicates success.
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Get a value from the response data using dot notation.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->data;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
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
