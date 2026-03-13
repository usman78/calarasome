<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        DB::statement(<<<'SQL'
            ALTER TABLE provider_schedules
            ADD CONSTRAINT provider_schedules_valid_time_window_chk
            CHECK (end_time > start_time)
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE provider_schedules
            ADD CONSTRAINT provider_schedules_no_overlap_excl
            EXCLUDE USING gist (
                provider_id WITH =,
                day_of_week WITH =,
                daterange(
                    COALESCE(effective_from, '-infinity'::date),
                    COALESCE(effective_until, 'infinity'::date),
                    '[]'
                ) WITH &&,
                int4range(
                    ((EXTRACT(HOUR FROM start_time)::int * 60) + EXTRACT(MINUTE FROM start_time)::int),
                    ((EXTRACT(HOUR FROM end_time)::int * 60) + EXTRACT(MINUTE FROM end_time)::int),
                    '[)'
                ) WITH &&
            )
            WHERE (is_active)
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE provider_schedules DROP CONSTRAINT IF EXISTS provider_schedules_no_overlap_excl');
        DB::statement('ALTER TABLE provider_schedules DROP CONSTRAINT IF EXISTS provider_schedules_valid_time_window_chk');
    }
};
