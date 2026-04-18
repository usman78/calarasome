<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_delivery_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('context_type', 80)->nullable();
            $table->unsignedBigInteger('context_id')->nullable();
            $table->string('mailable', 160);
            $table->string('recipient_email')->nullable();
            $table->string('status', 20);
            $table->string('failure_reason', 80)->nullable();
            $table->string('failure_class', 160)->nullable();
            $table->text('failure_message')->nullable();
            $table->string('suggested_action', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'resolved_at'], 'email_logs_status_resolved_idx');
            $table->index(['context_type', 'context_id'], 'email_logs_context_idx');
            $table->index(['clinic_id', 'created_at'], 'email_logs_clinic_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_delivery_logs');
    }
};
