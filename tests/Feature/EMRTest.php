<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitDiagnosis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EMRTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->patient = Patient::factory()->create();
    }

    // ── Access ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/emr')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_emr(): void
    {
        $this->actingAs($this->user)->get('/emr')->assertOk();
    }

    // ── Store Visit ──────────────────────────────────────────────────────────

    public function test_can_open_new_visit(): void
    {
        $this->actingAs($this->user)
            ->post('/emr', [
                'patient_id'      => $this->patient->id,
                'doctor_name'     => 'Dr. Test',
                'visit_date'      => now()->format('Y-m-d'),
                'chief_complaint' => 'Sakit kepala',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('visits', [
            'patient_id'      => $this->patient->id,
            'chief_complaint' => 'Sakit kepala',
            'status'          => 'open',
        ]);
    }

    public function test_patient_must_exist_when_opening_visit(): void
    {
        $this->actingAs($this->user)
            ->post('/emr', [
                'patient_id'  => 9999,
                'doctor_name' => 'Dr. Test',
                'visit_date'  => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('patient_id');
    }

    // ── SOAP Notes ───────────────────────────────────────────────────────────

    public function test_can_save_soap_notes(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->patch("/emr/{$visit->id}/soap", [
                'soap_s' => 'Patient complains of headache for 3 days.',
                'soap_o' => 'BP 120/80, Temp 37.1C',
                'soap_a' => 'Tension headache',
                'soap_p' => 'Prescribe paracetamol, rest advised.',
            ])
            ->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('Tension headache', $visit->soap_a);
    }

    // ── Vitals ───────────────────────────────────────────────────────────────

    public function test_can_store_vitals(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post("/emr/{$visit->id}/vitals", [
                'bp_systolic'  => 120,
                'bp_diastolic' => 80,
                'heart_rate'   => 72,
                'temperature'  => 36.8,
                'spo2'         => 99,
                'weight'       => 65,
                'height'       => 170,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('visit_vitals', [
            'visit_id'     => $visit->id,
            'bp_systolic'  => 120,
            'bp_diastolic' => 80,
        ]);
    }

    public function test_vitals_validates_physiological_ranges(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post("/emr/{$visit->id}/vitals", [
                'bp_systolic' => 999,
                'spo2'        => 200,
            ])
            ->assertSessionHasErrors(['bp_systolic', 'spo2']);
    }

    // ── Diagnoses ────────────────────────────────────────────────────────────

    public function test_can_add_diagnosis(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post("/emr/{$visit->id}/diagnoses", [
                'icd_code'    => 'G43.9',
                'description' => 'Migraine, unspecified',
                'type'        => 'primary',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('visit_diagnoses', [
            'visit_id'    => $visit->id,
            'icd_code'    => 'G43.9',
            'type'        => 'primary',
        ]);
    }

    public function test_diagnosis_type_must_be_primary_or_secondary(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post("/emr/{$visit->id}/diagnoses", [
                'icd_code'    => 'G43.9',
                'description' => 'Migraine',
                'type'        => 'tertiary',
            ])
            ->assertSessionHasErrors('type');
    }

    public function test_can_delete_diagnosis(): void
    {
        $visit     = Visit::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);
        $diagnosis = VisitDiagnosis::create([
            'visit_id'    => $visit->id,
            'icd_code'    => 'J00',
            'description' => 'Common cold',
            'type'        => 'primary',
        ]);

        $this->actingAs($this->user)
            ->delete("/emr/{$visit->id}/diagnoses/{$diagnosis->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('visit_diagnoses', ['id' => $diagnosis->id]);
    }

    // ── Close Visit ──────────────────────────────────────────────────────────

    public function test_can_close_visit(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->patch("/emr/{$visit->id}/close")
            ->assertSessionHas('success');

        $visit->refresh();
        $this->assertEquals('closed', $visit->status);
        $this->assertNotNull($visit->signed_at);
        $this->assertEquals($this->user->name, $visit->signed_by);
    }

    // ── Destroy Visit ────────────────────────────────────────────────────────

    public function test_can_delete_visit(): void
    {
        $visit = Visit::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->delete("/emr/{$visit->id}")
            ->assertRedirect(route('emr'));

        $this->assertDatabaseMissing('visits', ['id' => $visit->id]);
    }
}
