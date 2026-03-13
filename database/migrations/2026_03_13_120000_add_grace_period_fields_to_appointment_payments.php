<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_payments', function (Blueprint $table): void {
            $table->dateTime('grace_started_at')->nullable()->after('failed_at');
            $table->dateTime('grace_expires_at')->nullable()->after('grace_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_payments', function (Blueprint $table): void {
            $table->dropColumn(['grace_started_at', 'grace_expires_at']);
        });
    }
};
