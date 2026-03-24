<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_payments', function (Blueprint $table): void {
            $table->dateTime('voided_at')->nullable()->after('captured_at');
            $table->string('refund_id')->nullable()->after('voided_at');
            $table->dateTime('refunded_at')->nullable()->after('refund_id');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_payments', function (Blueprint $table): void {
            $table->dropColumn(['voided_at', 'refund_id', 'refunded_at']);
        });
    }
};
