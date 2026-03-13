<?php

use App\Models\AppointmentPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('records setup intent payment method on webhook', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $payment = AppointmentPayment::factory()->create([
        'stripe_setup_intent_id' => 'seti_test_123',
        'stripe_payment_method_id' => null,
        'status' => 'requires_payment_method',
    ]);

    $payload = json_encode([
        'type' => 'setup_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'seti_test_123',
                'payment_method' => 'pm_test_123',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $timestamp = time();
    $signedPayload = $timestamp.'.'.$payload;
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_test');

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    )->assertOk();

    $payment->refresh();
    expect($payment->stripe_payment_method_id)->toBe('pm_test_123');
    expect($payment->status)->toBe('pending_setup');
});

it('records payment intent status updates on webhook', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $payment = AppointmentPayment::factory()->create([
        'stripe_payment_intent_id' => 'pi_test_123',
        'status' => 'requires_payment_method',
        'authorized_at' => null,
        'captured_at' => null,
    ]);

    $payload = json_encode([
        'type' => 'payment_intent.requires_capture',
        'data' => [
            'object' => [
                'id' => 'pi_test_123',
                'status' => 'requires_capture',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    )->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe('requires_capture');
    expect($payment->authorized_at)->not->toBeNull();

    $payload = json_encode([
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_123',
                'status' => 'succeeded',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    )->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe('succeeded');
    expect($payment->captured_at)->not->toBeNull();
});

it('records payment intent failures on webhook', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $payment = AppointmentPayment::factory()->create([
        'stripe_payment_intent_id' => 'pi_fail_123',
        'status' => 'requires_payment_method',
        'failed_at' => null,
    ]);

    $payload = json_encode([
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_fail_123',
                'status' => 'failed',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    )->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe('failed');
    expect($payment->failed_at)->not->toBeNull();
});

it('records payment intent cancellations on webhook', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $payment = AppointmentPayment::factory()->create([
        'stripe_payment_intent_id' => 'pi_cancel_123',
        'status' => 'requires_capture',
        'failed_at' => null,
    ]);

    $payload = json_encode([
        'type' => 'payment_intent.canceled',
        'data' => [
            'object' => [
                'id' => 'pi_cancel_123',
                'status' => 'canceled',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    )->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe('canceled');
    expect($payment->failed_at)->not->toBeNull();
});

it('records charge refunds on webhook', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $payment = AppointmentPayment::factory()->create([
        'stripe_payment_intent_id' => 'pi_refund_123',
        'status' => 'succeeded',
        'failed_at' => null,
    ]);

    $payload = json_encode([
        'type' => 'charge.refunded',
        'data' => [
            'object' => [
                'id' => 'ch_refund_123',
                'payment_intent' => 'pi_refund_123',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    )->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe('refunded');
    expect($payment->failed_at)->not->toBeNull();
});

it('records payment intent amount capturable updates on webhook', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $payment = AppointmentPayment::factory()->create([
        'stripe_payment_intent_id' => 'pi_cap_123',
        'status' => 'requires_payment_method',
        'authorized_at' => null,
    ]);

    $payload = json_encode([
        'type' => 'payment_intent.amount_capturable_updated',
        'data' => [
            'object' => [
                'id' => 'pi_cap_123',
                'status' => 'requires_capture',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    )->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe('requires_capture');
    expect($payment->authorized_at)->not->toBeNull();
});

it('records charge disputes on webhook', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test']);

    $payment = AppointmentPayment::factory()->create([
        'stripe_payment_intent_id' => 'pi_dispute_123',
        'status' => 'succeeded',
        'failed_at' => null,
    ]);

    $payload = json_encode([
        'type' => 'charge.dispute.created',
        'data' => [
            'object' => [
                'id' => 'dp_123',
                'charge' => 'ch_123',
                'payment_intent' => 'pi_dispute_123',
            ],
        ],
    ], JSON_UNESCAPED_SLASHES);

    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
        ],
        $payload
    )->assertOk();

    $payment->refresh();
    expect($payment->status)->toBe('disputed');
    expect($payment->failed_at)->not->toBeNull();
});
