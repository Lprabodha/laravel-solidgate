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
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $eventType = $this->resolveEventType($request, $payload);

        Log::info('SolidGate webhook received', [
            'event' => $eventType,
            'event_id' => $request->header('solidgate-event-id'),
            'payload' => $payload,
        ]);

        event(new SolidGateWebhookReceived($eventType, $payload));

        return response()->json(['status' => 'ok']);
    }

    /**
     * Resolve the SolidGate webhook event type from request headers or payload.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function resolveEventType(Request $request, array $payload): string
    {
        return $request->header('solidgate-event-type')
            ?? $request->header('Solidgate-Event-Type')
            ?? $payload['event']
            ?? $payload['type']
            ?? 'unknown';
    }
}
