<?php

namespace App\Http\Controllers;

use App\Services\AppointmentPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, AppointmentPaymentService $paymentService): Response
    {
        $payload = $request->getContent();
        $signatureHeader = $request->header('Stripe-Signature', '');
        $secret = config('services.stripe.webhook_secret');

        if (! $secret || ! $this->isValidSignature($payload, $signatureHeader, $secret)) {
            return response('Invalid signature', 400);
        }

        $event = json_decode($payload, true);
        $type = $event['type'] ?? '';

        if ($type === 'setup_intent.succeeded') {
            $setupIntent = $event['data']['object'] ?? [];
            $paymentService->recordSetupIntentSucceeded(
                $setupIntent['id'] ?? '',
                $setupIntent['payment_method'] ?? null
            );
        }

        if ($type === 'payment_intent.succeeded') {
            $paymentIntent = $event['data']['object'] ?? [];
            $paymentService->recordPaymentIntentStatus(
                $paymentIntent['id'] ?? '',
                $paymentIntent['status'] ?? null
            );
        }

        if ($type === 'payment_intent.requires_capture') {
            $paymentIntent = $event['data']['object'] ?? [];
            $paymentService->recordPaymentIntentStatus(
                $paymentIntent['id'] ?? '',
                $paymentIntent['status'] ?? null
            );
        }

        if ($type === 'payment_intent.payment_failed') {
            $paymentIntent = $event['data']['object'] ?? [];
            $paymentService->recordPaymentIntentStatus(
                $paymentIntent['id'] ?? '',
                $paymentIntent['status'] ?? 'failed'
            );
        }

        if ($type === 'payment_intent.canceled') {
            $paymentIntent = $event['data']['object'] ?? [];
            $paymentService->recordPaymentIntentStatus(
                $paymentIntent['id'] ?? '',
                $paymentIntent['status'] ?? 'canceled'
            );
        }

        if ($type === 'charge.refunded') {
            $charge = $event['data']['object'] ?? [];
            $paymentService->recordChargeRefunded(
                $charge['payment_intent'] ?? '',
                $charge['id'] ?? null
            );
        }

        if ($type === 'payment_intent.amount_capturable_updated') {
            $paymentIntent = $event['data']['object'] ?? [];
            $paymentService->recordPaymentIntentStatus(
                $paymentIntent['id'] ?? '',
                $paymentIntent['status'] ?? 'requires_capture'
            );
        }

        if ($type === 'charge.dispute.created') {
            $charge = $event['data']['object'] ?? [];
            $paymentService->recordChargeDispute(
                $charge['payment_intent'] ?? '',
                $charge['id'] ?? null
            );
        }

        return response('ok', 200);
    }

    private function isValidSignature(string $payload, string $signatureHeader, string $secret): bool
    {
        if ($signatureHeader === '') {
            return false;
        }

        $parts = collect(explode(',', $signatureHeader))
            ->mapWithKeys(function (string $segment): array {
                [$key, $value] = array_pad(explode('=', $segment, 2), 2, null);

                return [$key => $value];
            });

        $timestamp = $parts->get('t');
        $signature = $parts->get('v1');

        if (! $timestamp || ! $signature) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp.'.'.$payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }
}
