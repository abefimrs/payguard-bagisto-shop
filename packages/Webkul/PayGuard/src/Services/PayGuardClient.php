<?php

namespace Webkul\PayGuard\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around the PayGuard API.
 *
 * Uses Laravel's HTTP client directly (rather than depending solely on the
 * payguard/sdk package internals) so this keeps working even if the SDK's
 * method signatures change — it only relies on the documented REST contract
 * at https://app.sourcemonkey.online/docs
 */
class PayGuardClient
{
    protected string $apiKey;

    protected string $baseUrl;

    public function __construct(?string $apiKey = null, ?string $baseUrl = null)
    {
        $this->apiKey = $apiKey ?? (string) core()->getConfigData('sales.payment_methods.payguard.api_key');
        $this->baseUrl = rtrim($baseUrl ?? (string) core()->getConfigData('sales.payment_methods.payguard.base_url'), '/');

        if (! $this->apiKey || ! $this->baseUrl) {
            throw new RuntimeException('PayGuard is not configured. Set the API key and base URL under Configuration → Sales → PayGuard Settings.');
        }
    }

    protected function client()
    {
        return Http::withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->baseUrl($this->baseUrl);
    }

    /**
     * Create a pending transaction. Returns the decoded `data` payload.
     */
    public function createTransaction(array $payload): array
    {
        $response = $this->client()->post('/transactions', $payload);

        if ($response->failed()) {
            throw new RuntimeException(
                'PayGuard: failed to create transaction — '.($response->json('error') ?? $response->body())
            );
        }

        return $response->json('data');
    }

    public function getTransaction(int $transactionId): array
    {
        $response = $this->client()->get("/transactions/{$transactionId}");

        if ($response->failed()) {
            throw new RuntimeException(
                'PayGuard: failed to fetch transaction — '.($response->json('error') ?? $response->body())
            );
        }

        return $response->json('data');
    }

    public function initiateBkash(int $transactionId): array
    {
        $response = $this->client()->post("/bkash/initiate/{$transactionId}");

        if ($response->failed()) {
            throw new RuntimeException(
                'PayGuard: failed to initiate bKash payment — '.($response->json('error') ?? $response->body())
            );
        }

        return $response->json('data');
    }

    public function initiateNagad(int $transactionId): array
    {
        $response = $this->client()->post("/nagad/initiate/{$transactionId}");

        if ($response->failed()) {
            throw new RuntimeException(
                'PayGuard: failed to initiate Nagad payment — '.($response->json('error') ?? $response->body())
            );
        }

        return $response->json('data');
    }

    public function queryBkash(int $transactionId): array
    {
        $response = $this->client()->post("/bkash/query/{$transactionId}");

        return $response->json('data') ?? [];
    }

    public function refundBkash(int $transactionId, float $amount): array
    {
        $response = $this->client()->post("/bkash/refund/{$transactionId}", [
            'amount' => $amount,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'PayGuard: refund failed — '.($response->json('error') ?? $response->body())
            );
        }

        return $response->json('data');
    }

    /**
     * Verify an inbound webhook's HMAC-SHA256 signature.
     */
    public static function verifySignature(string $rawBody, ?string $receivedSignature, string $secret): bool
    {
        if (! $receivedSignature) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, $receivedSignature);
    }
}
