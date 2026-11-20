<?php

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Paystack API (https://paystack.com/docs/api/).
 * Uses the hosted checkout page, so card and mobile-money details never touch this server.
 */
class PaystackClient
{
    public function configured(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    public function isTestMode(): bool
    {
        return str_starts_with((string) config('services.paystack.secret_key'), 'sk_test_');
    }

    /**
     * Starts a transaction and returns Paystack's checkout URL.
     *
     * @param  array<string,mixed>  $metadata
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initialize(string $email, int $amountMinor, string $reference, string $callbackUrl, array $metadata = []): array
    {
        $data = $this->call('post', '/transaction/initialize', [
            'email' => $email,
            'amount' => $amountMinor,
            'currency' => config('services.paystack.currency', 'GHS'),
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ]);

        if (empty($data['authorization_url'])) {
            throw new PaymentGatewayException('The payment provider did not return a payment page.');
        }

        return $data;
    }

    /**
     * Asks Paystack for the real outcome of a transaction.
     *
     * @return array<string,mixed> Paystack's transaction data (status, amount, currency, channel, ...)
     */
    public function verify(string $reference): array
    {
        return $this->call('get', '/transaction/verify/'.rawurlencode($reference));
    }

    /** Checks the x-paystack-signature header of a webhook (HMAC SHA-512 of the raw body). */
    public function validWebhookSignature(string $payload, ?string $signature): bool
    {
        $secret = (string) config('services.paystack.secret_key');

        return $secret !== '' && is_string($signature) && $signature !== ''
            && hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }

    /** @return array<string,mixed> */
    private function call(string $method, string $path, array $body = []): array
    {
        if (! $this->configured()) {
            throw new PaymentGatewayException('Online payment is not set up yet.');
        }

        try {
            $response = $this->http()->{$method}($path, $method === 'get' ? null : $body);
        } catch (ConnectionException $e) {
            Log::warning('Paystack connection failed', ['path' => $path, 'error' => $e->getMessage()]);

            throw new PaymentGatewayException('The payment provider could not be reached. Please try again in a moment.');
        }

        $json = $response->json();
        if (! $response->successful() || ! is_array($json) || ($json['status'] ?? false) !== true) {
            Log::warning('Paystack request failed', ['path' => $path, 'http' => $response->status(), 'message' => $json['message'] ?? null]);

            throw new PaymentGatewayException('The payment provider refused the request'.(isset($json['message']) ? ': '.mb_substr((string) $json['message'], 0, 150) : '.'));
        }

        return (array) ($json['data'] ?? []);
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('services.paystack.base_url'))
            ->withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->retry(2, 300, throw: false);
    }
}
