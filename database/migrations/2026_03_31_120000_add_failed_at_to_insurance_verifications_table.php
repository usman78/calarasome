<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('insurance_verifications')) {
            return;
        }

        if (! Schema::hasColumn('insurance_verifications', 'failed_at')) {
            Schema::table('insurance_verifications', function (Blueprint $table): void {
                $table->dateTime('failed_at')->nullable()->after('verified_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('insurance_verifications')) {
            return;
        }

        if (Schema::hasColumn('insurance_verifications', 'failed_at')) {
            Schema::table('insurance_verifications', function (Blueprint $table): void {
                $table->dropColumn('failed_at');
            });
        }
    }
};
