<?php

namespace Lahiru\LaravelSolidGate\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a SolidGate webhook is received.
 */
class SolidGateWebhookReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $eventType,
        public readonly array $payload
    ) {
        //
    }
}
