<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->string('no_show_previous_status')->nullable()->after('status');
            $table->dateTime('no_show_marked_at')->nullable()->after('no_show_previous_status');
            $table->dateTime('no_show_reversible_until')->nullable()->after('no_show_marked_at');
            $table->dateTime('no_show_reversed_at')->nullable()->after('no_show_reversible_until');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn([
                'no_show_previous_status',
                'no_show_marked_at',
                'no_show_reversible_until',
                'no_show_reversed_at',
            ]);
        });
    }
};
