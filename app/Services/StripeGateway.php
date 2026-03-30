<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class StripeGateway
{
    public function createPaymentIntent(array $payload): array
    {
        return $this->post('payment_intents', $payload);
    }

    public function createSetupIntent(array $payload): array
    {
        return $this->post('setup_intents', $payload);
    }

    public function retrieveSetupIntent(string $id): array
    {
        return $this->get("setup_intents/{$id}");
    }

    public function cancelPaymentIntent(string $id): array
    {
        return $this->post("payment_intents/{$id}/cancel", []);
    }

    public function capturePaymentIntent(string $id, array $payload = []): array
    {
        return $this->post("payment_intents/{$id}/capture", $payload);
    }

    public function createRefund(array $payload): array
    {
        return $this->post('refunds', $payload);
    }

    private function post(string $path, array $payload): array
    {
        $payload = $this->normalizeStripePayload($payload);
        $response = $this->request()->asForm()->post($this->url($path), $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Stripe request failed: '.$response->body());
        }

        return $response->json();
    }

    private function get(string $path): array
    {
        $response = $this->request()->get($this->url($path));

        if (! $response->successful()) {
            throw new RuntimeException('Stripe request failed: '.$response->body());
        }

        return $response->json();
    }

    private function request(): PendingRequest
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        return Http::withToken($secret);
    }

    private function url(string $path): string
    {
        return 'https://api.stripe.com/v1/'.$path;
    }

    private function normalizeStripePayload(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeStripePayload($value);
                continue;
            }

            if (is_bool($value)) {
                $normalized[$key] = $value ? 'true' : 'false';
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }
}
