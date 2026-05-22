<?php

namespace Tests\Feature;

use App\Models\MedicalCertificate;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MCTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->patient = Patient::factory()->create();
        $this->visit   = Visit::factory()->create([
            'patient_id'  => $this->patient->id,
            'user_id'     => $this->user->id,
            'doctor_name' => 'Dr. Ahmad',
        ]);
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_can_issue_medical_certificate(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", [
                'start_date' => '2025-01-10',
                'days'       => 3,
                'diagnosis'  => 'Acute Respiratory Infection',
                'notes'      => 'Bed rest advised',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('medical_certificates', [
            'visit_id'   => $this->visit->id,
            'patient_id' => $this->patient->id,
            'days'       => 3,
            'issued_by'  => 'Dr. Ahmad',
        ]);
    }

    public function test_mc_number_is_auto_generated(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", [
                'start_date' => '2025-01-10',
                'days'       => 1,
            ])
            ->assertSessionHas('success');

        $mc = MedicalCertificate::latest('id')->first();
        $this->assertNotNull($mc);
        $this->assertMatchesRegularExpression('/^MC-\d{4}-\d{4}$/', $mc->mc_number);
    }

    public function test_end_date_is_calculated_from_start_date_and_days(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", [
                'start_date' => '2025-03-01',
                'days'       => 5,
            ])
            ->assertSessionHas('success');

        $mc = MedicalCertificate::latest('id')->first();
        $this->assertNotNull($mc);
        $this->assertEquals('2025-03-05', $mc->end_date->format('Y-m-d'));
    }

    public function test_one_day_mc_has_same_start_and_end_date(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", [
                'start_date' => '2025-06-15',
                'days'       => 1,
            ])
            ->assertSessionHas('success');

        $mc = MedicalCertificate::latest('id')->first();
        $this->assertNotNull($mc);
        $this->assertEquals('2025-06-15', $mc->start_date->format('Y-m-d'));
        $this->assertEquals('2025-06-15', $mc->end_date->format('Y-m-d'));
    }

    public function test_start_date_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", ['days' => 2])
            ->assertSessionHasErrors('start_date');
    }

    public function test_days_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", ['start_date' => '2025-01-10'])
            ->assertSessionHasErrors('days');
    }

    public function test_days_must_be_at_least_one(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", [
                'start_date' => '2025-01-10',
                'days'       => 0,
            ])
            ->assertSessionHasErrors('days');
    }

    public function test_days_cannot_exceed_365(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", [
                'start_date' => '2025-01-10',
                'days'       => 366,
            ])
            ->assertSessionHasErrors('days');
    }

    public function test_start_date_must_be_a_valid_date(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", [
                'start_date' => 'not-a-date',
                'days'       => 2,
            ])
            ->assertSessionHasErrors('start_date');
    }

    public function test_guests_cannot_issue_mc(): void
    {
        $this->post("/emr/{$this->visit->id}/mc", [
            'start_date' => '2025-01-10',
            'days'       => 1,
        ])->assertRedirect('/login');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_can_delete_medical_certificate(): void
    {
        $mc = MedicalCertificate::create([
            'patient_id' => $this->patient->id,
            'visit_id'   => $this->visit->id,
            'issued_by'  => 'Dr. Ahmad',
            'issue_date' => now()->toDateString(),
            'start_date' => '2025-01-10',
            'end_date'   => '2025-01-12',
            'days'       => 3,
        ]);

        $this->actingAs($this->user)
            ->delete("/mc/{$mc->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('medical_certificates', ['id' => $mc->id]);
    }

    public function test_guests_cannot_delete_mc(): void
    {
        $mc = MedicalCertificate::create([
            'patient_id' => $this->patient->id,
            'visit_id'   => $this->visit->id,
            'issued_by'  => 'Dr. Ahmad',
            'issue_date' => now()->toDateString(),
            'start_date' => '2025-01-10',
            'end_date'   => '2025-01-10',
            'days'       => 1,
        ]);

        $this->delete("/mc/{$mc->id}")->assertRedirect('/login');
    }

    // ── Multiple MCs per visit ────────────────────────────────────────────────

    public function test_multiple_mcs_can_be_issued_for_same_visit(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", ['start_date' => '2025-01-10', 'days' => 1])
            ->assertSessionHas('success');

        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", ['start_date' => '2025-02-10', 'days' => 2])
            ->assertSessionHas('success');

        $this->assertEquals(2, MedicalCertificate::where('visit_id', $this->visit->id)->count());
    }

    public function test_mc_numbers_are_sequential(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/mc", ['start_date' => '2025-01-10', 'days' => 1])
            ->assertSessionHas('success');

        $visit2 = Visit::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);
        $this->actingAs($this->user)
            ->post("/emr/{$visit2->id}/mc", ['start_date' => '2025-01-11', 'days' => 1])
            ->assertSessionHas('success');

        $year = now()->year;
        $mcs  = MedicalCertificate::orderBy('id')->get();
        $this->assertEquals("MC-{$year}-0001", $mcs[0]->mc_number);
        $this->assertEquals("MC-{$year}-0002", $mcs[1]->mc_number);
    }
}
