<?php

namespace Lahiru\LaravelSolidGate\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Lahiru\LaravelSolidGate\Events\SolidGateWebhookReceived;

/**
 * Controller for handling SolidGate webhook events.
 */
class SolidGateWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventType = $payload['event'] ?? $payload['type'] ?? 'unknown';

        Log::info('SolidGate webhook received', [
            'event' => $eventType,
            'payload' => $payload,
        ]);

        // Dispatch Laravel event for webhook handling
        event(new SolidGateWebhookReceived($eventType, $payload));

        // You can add custom webhook handling logic here
        // For example:
        // match ($eventType) {
        //     'charge.success' => $this->handleChargeSuccess($payload),
        //     'charge.failed' => $this->handleChargeFailed($payload),
        //     'refund.success' => $this->handleRefundSuccess($payload),
        //     default => null,
        // };

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle charge success event.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleChargeSuccess(array $payload): void
    {
        // Implement your charge success logic here
    }

    /**
     * Handle charge failed event.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleChargeFailed(array $payload): void
    {
        // Implement your charge failed logic here
    }

    /**
     * Handle refund success event.
     *
     * @param  array  $payload
     * @return void
     */
    protected function handleRefundSuccess(array $payload): void
    {
        // Implement your refund success logic here
    }
}
