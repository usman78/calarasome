<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->foreignId('owner_id')->nullable()->after('min_booking_notice_hours')->constrained('users')->nullOnDelete();
            $table->index('owner_id');
        });

        $firstAdminId = User::query()
            ->where('is_admin', true)
            ->orderBy('id')
            ->value('id');

        if ($firstAdminId) {
            DB::table('clinics')
                ->whereNull('owner_id')
                ->update(['owner_id' => $firstAdminId]);
        }
    }

    public function down(): void
    {
        Schema::table('clinics', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('owner_id');
        });
    }
};
