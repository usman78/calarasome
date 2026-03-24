<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->dateTime('slot_datetime');
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('current_round')->default(0);
            $table->dateTime('next_round_at')->nullable();
            $table->dateTime('last_notified_at')->nullable();
            $table->foreignId('claimed_by_waitlist_entry_id')->nullable()->constrained('waitlist_entries')->nullOnDelete();
            $table->foreignId('claimed_appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->dateTime('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'status']);
            $table->index(['slot_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_notifications');
    }
};
