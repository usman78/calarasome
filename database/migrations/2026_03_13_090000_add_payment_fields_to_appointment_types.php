<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointment_types', function (Blueprint $table): void {
            $table->boolean('is_medical')->default(false)->after('is_active');
            $table->unsignedInteger('deposit_amount_cents')->default(0)->after('is_medical');
            $table->string('deposit_currency', 10)->default('usd')->after('deposit_amount_cents');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_types', function (Blueprint $table): void {
            $table->dropColumn(['is_medical', 'deposit_amount_cents', 'deposit_currency']);
        });
    }
};
