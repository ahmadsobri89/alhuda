<?php

namespace Tests\Unit;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitModelTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = Patient::factory()->create();
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function test_visit_belongs_to_an_appointment(): void
    {
        $appt  = Appointment::factory()->create(['patient_id' => $this->patient->id]);
        $visit = Visit::factory()->create([
            'patient_id'     => $this->patient->id,
            'appointment_id' => $appt->id,
        ]);

        $this->assertInstanceOf(Appointment::class, $visit->appointment);
        $this->assertEquals($appt->id, $visit->appointment->id);
    }

    public function test_visit_appointment_is_null_for_walk_in(): void
    {
        $visit = Visit::factory()->create([
            'patient_id'     => $this->patient->id,
            'appointment_id' => null,
        ]);

        $this->assertNull($visit->appointment);
    }

    public function test_appointment_has_one_visit(): void
    {
        $appt  = Appointment::factory()->create(['patient_id' => $this->patient->id]);
        $visit = Visit::factory()->create([
            'patient_id'     => $this->patient->id,
            'appointment_id' => $appt->id,
        ]);

        $this->assertInstanceOf(Visit::class, $appt->fresh()->visit);
        $this->assertEquals($visit->id, $appt->fresh()->visit->id);
    }

    public function test_appointment_visit_is_null_until_emr_opened(): void
    {
        $appt = Appointment::factory()->create(['patient_id' => $this->patient->id]);

        $this->assertNull($appt->visit);
    }

    public function test_appointment_id_is_mass_assignable(): void
    {
        $appt  = Appointment::factory()->create(['patient_id' => $this->patient->id]);
        $visit = Visit::create([
            'patient_id'     => $this->patient->id,
            'user_id'        => User::factory()->create()->id,
            'appointment_id' => $appt->id,
            'doctor_name'    => 'Dr. Test',
            'visit_date'     => now()->toDateString(),
            'status'         => 'open',
        ]);

        $this->assertEquals($appt->id, $visit->fresh()->appointment_id);
    }
}
