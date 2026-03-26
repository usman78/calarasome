<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('timezone', 50)->default('America/New_York');
            $table->timestamps();
        });

        Schema::create('providers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('title')->nullable();
            $table->string('specialization')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('default_appointment_types')->nullable();
            $table->unsignedSmallInteger('booking_buffer_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_accepting_new_patients')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->dateTime('last_auto_assigned_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'is_active']);
            $table->index(['clinic_id', 'last_auto_assigned_at']);
        });

        Schema::create('appointment_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['clinic_id', 'is_active']);
        });

        Schema::create('provider_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->json('appointment_type_ids')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['provider_id', 'day_of_week']);
            $table->unique([
                'provider_id',
                'day_of_week',
                'start_time',
                'end_time',
                'effective_from',
                'effective_until',
            ], 'provider_schedule_unique_window');
        });

        Schema::create('provider_blocked_times', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'start_datetime', 'end_datetime'], 'provider_blocked_range_idx');
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->date('date_of_birth');
            $table->boolean('is_shared_email_account')->default(false);
            $table->json('communication_consent')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'email', 'date_of_birth']);
            $table->index(['clinic_id', 'email', 'full_name']);
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('slot_datetime');
            $table->string('status')->default('pending');
            $table->json('triage_data')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'slot_datetime']);
            $table->index(['clinic_id', 'status']);
        });

        Schema::create('slot_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->constrained()->cascadeOnDelete();
            $table->dateTime('slot_datetime');
            $table->string('session_token', 64)->unique();
            $table->dateTime('reserved_at');
            $table->dateTime('expires_at');
            $table->foreignId('converted_to_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->dateTime('released_at')->nullable();
            $table->timestamps();

            $table->index(['provider_id', 'slot_datetime']);
            $table->index(['expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_reservations');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('provider_blocked_times');
        Schema::dropIfExists('provider_schedules');
        Schema::dropIfExists('appointment_types');
        Schema::dropIfExists('providers');
        Schema::dropIfExists('clinics');
    }
};
