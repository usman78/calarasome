<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_merge_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('clinic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('target_patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('merged_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['clinic_id', 'source_patient_id']);
            $table->index(['target_patient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_merge_logs');
    }
};
