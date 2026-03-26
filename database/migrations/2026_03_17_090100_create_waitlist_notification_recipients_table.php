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
            $table->foreignId('waitlist_notification_id');
            $table->foreignId('waitlist_entry_id');
            $table->string('token_hash', 64)->unique();
            $table->dateTime('expires_at');
            $table->string('status', 20)->default('sent');
            $table->dateTime('notified_at')->nullable();
            $table->dateTime('claimed_at')->nullable();
            $table->timestamps();

            $table->index(['waitlist_notification_id', 'status'], 'wlnr_notif_status_idx');
            $table->index(['waitlist_entry_id'], 'wlnr_entry_idx');

            $table->foreign('waitlist_notification_id', 'wlnr_notif_fk')
                ->references('id')
                ->on('waitlist_notifications')
                ->cascadeOnDelete();
            $table->foreign('waitlist_entry_id', 'wlnr_entry_fk')
                ->references('id')
                ->on('waitlist_entries')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlist_notification_recipients');
    }
};
