<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waitlist_notification_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('waitlist_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('waitlist_entry_id')->constrained()->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->string('status', 20)->default('sent');
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['waitlist_notification_id', 'status']);
            $table->index(['waitlist_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_notification_recipients');
    }
};
