<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StarterSeeder extends Seeder
{
    /**
     * Create a known admin account and starter clinic for local/demo setup.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'is_admin' => true,
                'password' => Hash::make('password321'),
            ],
        );

        Clinic::query()
            ->withoutGlobalScopes()
            ->updateOrCreate(
                ['slug' => 'drresnik'],
                [
                    'name' => 'Dr Resnik Clinic',
                    'timezone' => 'America/New_York',
                    'min_booking_notice_hours' => 2,
                    'owner_id' => $admin->id,
                ],
            );
    }
}
