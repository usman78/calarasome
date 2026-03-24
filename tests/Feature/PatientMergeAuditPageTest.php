<?php

use App\Livewire\Admin\PatientMergeAuditPage;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\PatientMergeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('shows patient merge audit rows', function () {
    $clinic = Clinic::factory()->create(['name' => 'Audit Clinic']);
    $source = Patient::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Source Patient']);
    $target = Patient::factory()->create(['clinic_id' => $clinic->id, 'full_name' => 'Target Patient']);
    $admin = User::factory()->create(['is_admin' => true]);

    PatientMergeLog::query()->create([
        'clinic_id' => $clinic->id,
        'source_patient_id' => $source->id,
        'target_patient_id' => $target->id,
        'merged_by_user_id' => $admin->id,
        'payload' => ['note' => 'test'],
    ]);

    Livewire::actingAs($admin)
        ->test(PatientMergeAuditPage::class)
        ->assertSee('Patient Merge Audit Log')
        ->assertSee('Source Patient')
        ->assertSee('Target Patient');
});
