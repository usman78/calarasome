<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->constrained()->cascadeOnDelete();
            $table->string('strategy', 32);
            $table->string('status', 32);
            $table->unsignedInteger('amount_cents')->default(0);
            $table->string('currency', 10)->default('usd');
            $table->dateTime('auth_scheduled_for')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_setup_intent_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            $table->dateTime('authorized_at')->nullable();
            $table->dateTime('captured_at')->nullable();
            $table->dateTime('failed_at')->nullable();
            $table->timestamps();

            $table->unique('appointment_id');
            $table->index(['status', 'auth_scheduled_for']);
            $table->index('stripe_setup_intent_id');
            $table->index('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_payments');
    }
};
