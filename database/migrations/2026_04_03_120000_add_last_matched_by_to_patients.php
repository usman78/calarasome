<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('patients')) {
            return;
        }

        if (! Schema::hasColumn('patients', 'last_matched_by')) {
            Schema::table('patients', function (Blueprint $table): void {
                $table->string('last_matched_by', 40)->nullable()->after('is_shared_email_account');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('patients')) {
            return;
        }

        if (Schema::hasColumn('patients', 'last_matched_by')) {
            Schema::table('patients', function (Blueprint $table): void {
                $table->dropColumn('last_matched_by');
            });
        }
    }
};
