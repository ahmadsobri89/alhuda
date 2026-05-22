<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\TimeSlip;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSlipTest extends TestCase
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
            'doctor_name' => 'Dr. Azlan',
        ]);
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_can_issue_time_slip(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", [
                'arrival_time'   => '09:00',
                'departure_time' => '10:30',
                'purpose'        => 'Consultation',
                'notes'          => 'Urgent follow-up',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('time_slips', [
            'visit_id'       => $this->visit->id,
            'patient_id'     => $this->patient->id,
            'arrival_time'   => '09:00',
            'departure_time' => '10:30',
            'issued_by'      => 'Dr. Azlan',
        ]);
    }

    public function test_slip_number_is_auto_generated(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", [
                'arrival_time'   => '08:30',
                'departure_time' => '09:00',
            ]);

        $slip = TimeSlip::where('visit_id', $this->visit->id)->first();
        $this->assertNotNull($slip->slip_number);
        $this->assertMatchesRegularExpression('/^TS-\d{4}-\d{4}$/', $slip->slip_number);
    }

    public function test_arrival_time_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", [
                'departure_time' => '10:00',
            ])
            ->assertSessionHasErrors('arrival_time');
    }

    public function test_departure_time_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", [
                'arrival_time' => '09:00',
            ])
            ->assertSessionHasErrors('departure_time');
    }

    public function test_departure_time_must_be_after_arrival_time(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", [
                'arrival_time'   => '10:00',
                'departure_time' => '09:00',
            ])
            ->assertSessionHasErrors('departure_time');
    }

    public function test_departure_equal_to_arrival_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", [
                'arrival_time'   => '09:00',
                'departure_time' => '09:00',
            ])
            ->assertSessionHasErrors('departure_time');
    }

    public function test_times_must_be_hhmm_format(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", [
                'arrival_time'   => '9:00',
                'departure_time' => '10:00',
            ])
            ->assertSessionHasErrors('arrival_time');
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", [
                'arrival_time'   => '08:00',
                'departure_time' => '08:45',
            ])
            ->assertSessionHas('success');
    }

    public function test_guests_cannot_issue_time_slip(): void
    {
        $this->post("/emr/{$this->visit->id}/timeslip", [
            'arrival_time'   => '09:00',
            'departure_time' => '10:00',
        ])->assertRedirect('/login');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_can_delete_time_slip(): void
    {
        $slip = TimeSlip::create([
            'patient_id'     => $this->patient->id,
            'visit_id'       => $this->visit->id,
            'issued_by'      => 'Dr. Azlan',
            'slip_date'      => now()->toDateString(),
            'arrival_time'   => '09:00',
            'departure_time' => '10:00',
        ]);

        $this->actingAs($this->user)
            ->delete("/timeslip/{$slip->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('time_slips', ['id' => $slip->id]);
    }

    public function test_guests_cannot_delete_time_slip(): void
    {
        $slip = TimeSlip::create([
            'patient_id'     => $this->patient->id,
            'visit_id'       => $this->visit->id,
            'issued_by'      => 'Dr. Azlan',
            'slip_date'      => now()->toDateString(),
            'arrival_time'   => '09:00',
            'departure_time' => '10:00',
        ]);

        $this->delete("/timeslip/{$slip->id}")->assertRedirect('/login');
    }

    // ── Sequential numbering ─────────────────────────────────────────────────

    public function test_slip_numbers_are_sequential(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/timeslip", ['arrival_time' => '09:00', 'departure_time' => '09:30']);

        $visit2 = Visit::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);
        $this->actingAs($this->user)
            ->post("/emr/{$visit2->id}/timeslip", ['arrival_time' => '10:00', 'departure_time' => '10:30']);

        $year  = now()->year;
        $slips = TimeSlip::orderBy('id')->get();
        $this->assertEquals("TS-{$year}-0001", $slips[0]->slip_number);
        $this->assertEquals("TS-{$year}-0002", $slips[1]->slip_number);
    }
}
