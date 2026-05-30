<?php

namespace Lahiru\LaravelSolidGate\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Lahiru\LaravelSolidGate\Exceptions\SolidGateSignatureException;
use Lahiru\LaravelSolidGate\Support\SignatureValidator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to verify SolidGate webhook signatures.
 */
class VerifySolidGateSignature
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws SolidGateSignatureException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $publicKey = config('solidgate.webhook.public_key') ?? config('solidgate.public_key');
        $secretKey = config('solidgate.webhook.secret') ?? config('solidgate.secret_key');
        $signatureHeader = config('solidgate.webhook.signature_header', 'Signature');

        if (empty($publicKey) || empty($secretKey)) {
            Log::warning('SolidGate webhook verification skipped: missing credentials');

            return response()->json(['error' => 'Webhook verification not configured'], 500);
        }

        $receivedSignature = $request->header($signatureHeader);
        $receivedMerchant = $request->header('Merchant') ?? $request->header('merchant');
        $payload = $request->getContent();

        if ($receivedMerchant !== null && $receivedMerchant !== $publicKey) {
            Log::warning('SolidGate webhook merchant header mismatch', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid merchant'], 401);
        }

        try {
            SignatureValidator::validate($publicKey, $payload, $secretKey, $receivedSignature);
        } catch (SolidGateSignatureException $e) {
            Log::warning('SolidGate webhook signature validation failed', [
                'ip' => $request->ip(),
                'url' => $request->fullUrl(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
