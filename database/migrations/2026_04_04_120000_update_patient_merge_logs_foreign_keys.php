<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_merge_logs', function (Blueprint $table): void {
            $table->dropForeign(['source_patient_id']);
            $table->dropForeign(['target_patient_id']);
        });

        Schema::table('patient_merge_logs', function (Blueprint $table): void {
            $table->foreignId('source_patient_id')->nullable()->change();
            $table->foreignId('target_patient_id')->nullable()->change();
        });

        Schema::table('patient_merge_logs', function (Blueprint $table): void {
            $table->foreign('source_patient_id')->references('id')->on('patients')->nullOnDelete();
            $table->foreign('target_patient_id')->references('id')->on('patients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_merge_logs', function (Blueprint $table): void {
            $table->dropForeign(['source_patient_id']);
            $table->dropForeign(['target_patient_id']);
        });

        Schema::table('patient_merge_logs', function (Blueprint $table): void {
            $table->foreignId('source_patient_id')->nullable(false)->change();
            $table->foreignId('target_patient_id')->nullable(false)->change();
        });

        Schema::table('patient_merge_logs', function (Blueprint $table): void {
            $table->foreign('source_patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('target_patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }
};
