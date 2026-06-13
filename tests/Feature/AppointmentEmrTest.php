<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentEmrTest extends TestCase
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

    private function makeAppointment(array $overrides = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'patient_id'       => $this->patient->id,
            'user_id'          => $this->user->id,
            'doctor_name'      => 'Dr. Aiman Rashid',
            'appointment_date' => now()->format('Y-m-d'),
            'appointment_time' => '11:00',
            'reason'           => 'Demam dan sakit tekak',
            'status'           => 'confirmed',
        ], $overrides));
    }

    // ── Access ──────────────────────────────────────────────────────────────

    public function test_guests_cannot_start_emr_from_appointment(): void
    {
        $appt = $this->makeAppointment();

        $this->post("/appointments/{$appt->id}/emr")->assertRedirect('/login');

        $this->assertDatabaseCount('visits', 0);
    }

    // ── Create EMR ──────────────────────────────────────────────────────────

    public function test_doctor_can_start_emr_from_appointment(): void
    {
        $appt = $this->makeAppointment();

        $this->actingAs($this->user)
            ->post("/appointments/{$appt->id}/emr")
            ->assertRedirect();

        $this->assertDatabaseHas('visits', [
            'appointment_id' => $appt->id,
            'patient_id'     => $this->patient->id,
            'status'         => 'open',
        ]);
    }

    public function test_emr_is_prefilled_from_appointment_details(): void
    {
        $appt = $this->makeAppointment([
            'doctor_name' => 'Dr. Siti Aminah',
            'reason'      => 'Kawalan tekanan darah',
        ]);

        $this->actingAs($this->user)->post("/appointments/{$appt->id}/emr");

        $visit = Visit::where('appointment_id', $appt->id)->first();

        $this->assertNotNull($visit);
        $this->assertSame($this->patient->id, $visit->patient_id);
        $this->assertSame('Dr. Siti Aminah', $visit->doctor_name);
        $this->assertSame('Kawalan tekanan darah', $visit->chief_complaint);
        $this->assertSame($appt->appointment_date->format('Y-m-d'), $visit->visit_date->format('Y-m-d'));
        $this->assertSame($this->user->id, $visit->user_id);
    }

    public function test_start_emr_redirects_to_the_created_visit(): void
    {
        $appt = $this->makeAppointment();

        $this->actingAs($this->user)
            ->post("/appointments/{$appt->id}/emr")
            ->assertRedirect();

        $visit = Visit::where('appointment_id', $appt->id)->first();

        $this->actingAs($this->user)
            ->post("/appointments/{$appt->id}/emr")
            ->assertRedirect(route('emr', ['visit' => $visit->id]));
    }

    // ── Idempotency — no duplicate EMR per appointment ──────────────────────

    public function test_starting_emr_twice_reuses_the_same_visit(): void
    {
        $appt = $this->makeAppointment();

        $this->actingAs($this->user)->post("/appointments/{$appt->id}/emr");
        $this->actingAs($this->user)->post("/appointments/{$appt->id}/emr");

        $this->assertEquals(1, Visit::where('appointment_id', $appt->id)->count());
    }

    // ── Status workflow ─────────────────────────────────────────────────────

    public function test_starting_emr_marks_appointment_in_room(): void
    {
        $appt = $this->makeAppointment(['status' => 'waiting']);

        $this->actingAs($this->user)->post("/appointments/{$appt->id}/emr");

        $this->assertDatabaseHas('appointments', [
            'id'     => $appt->id,
            'status' => 'in_room',
        ]);
    }

    public function test_starting_emr_does_not_override_a_done_appointment_status(): void
    {
        $appt = $this->makeAppointment(['status' => 'done']);

        $this->actingAs($this->user)->post("/appointments/{$appt->id}/emr");

        $this->assertDatabaseHas('appointments', [
            'id'     => $appt->id,
            'status' => 'done',
        ]);
        // EMR is still opened for review.
        $this->assertDatabaseHas('visits', ['appointment_id' => $appt->id]);
    }

    public function test_reopening_existing_emr_does_not_change_appointment_status(): void
    {
        $appt = $this->makeAppointment(['status' => 'confirmed']);

        // First call opens EMR and moves appointment to in_room.
        $this->actingAs($this->user)->post("/appointments/{$appt->id}/emr");
        // Move it on to done, then re-open the EMR — status must stay done.
        $appt->update(['status' => 'done']);

        $this->actingAs($this->user)->post("/appointments/{$appt->id}/emr");

        $this->assertDatabaseHas('appointments', [
            'id'     => $appt->id,
            'status' => 'done',
        ]);
    }
}
