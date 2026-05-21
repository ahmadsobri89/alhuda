<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
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
        $this->get('/appointments')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_appointments(): void
    {
        $this->actingAs($this->user)->get('/appointments')->assertOk();
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_can_create_appointment(): void
    {
        $data = [
            'patient_id'       => $this->patient->id,
            'doctor_name'      => 'Dr. Ahmad',
            'appointment_date' => now()->addDay()->format('Y-m-d'),
            'appointment_time' => '09:00',
            'duration_minutes' => 30,
            'type'             => 'new',
            'reason'           => 'Fever',
        ];

        $this->actingAs($this->user)
            ->post('/appointments', $data)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'patient_id'  => $this->patient->id,
            'doctor_name' => 'Dr. Ahmad',
        ]);
    }

    public function test_patient_must_exist(): void
    {
        $this->actingAs($this->user)
            ->post('/appointments', [
                'patient_id'       => 9999,
                'doctor_name'      => 'Dr. Test',
                'appointment_date' => now()->addDay()->format('Y-m-d'),
                'appointment_time' => '09:00',
                'duration_minutes' => 30,
                'type'             => 'new',
            ])
            ->assertSessionHasErrors('patient_id');
    }

    public function test_appointment_time_must_match_hhmm_format(): void
    {
        $this->actingAs($this->user)
            ->post('/appointments', [
                'patient_id'       => $this->patient->id,
                'doctor_name'      => 'Dr. Test',
                'appointment_date' => now()->addDay()->format('Y-m-d'),
                'appointment_time' => '9:00',
                'duration_minutes' => 30,
                'type'             => 'new',
            ])
            ->assertSessionHasErrors('appointment_time');
    }

    public function test_duration_must_be_valid_option(): void
    {
        $this->actingAs($this->user)
            ->post('/appointments', [
                'patient_id'       => $this->patient->id,
                'doctor_name'      => 'Dr. Test',
                'appointment_date' => now()->addDay()->format('Y-m-d'),
                'appointment_time' => '09:00',
                'duration_minutes' => 20,
                'type'             => 'new',
            ])
            ->assertSessionHasErrors('duration_minutes');
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_can_update_appointment(): void
    {
        $appt = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->put("/appointments/{$appt->id}", [
                'patient_id'       => $this->patient->id,
                'doctor_name'      => 'Dr. Updated',
                'appointment_date' => now()->addDay()->format('Y-m-d'),
                'appointment_time' => '10:00',
                'duration_minutes' => 45,
                'type'             => 'follow_up',
                'status'           => 'confirmed',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appt->id, 'doctor_name' => 'Dr. Updated']);
    }

    // ── Status ───────────────────────────────────────────────────────────────

    public function test_can_update_appointment_status(): void
    {
        $appt = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
            'status'     => 'confirmed',
        ]);

        $this->actingAs($this->user)
            ->patch("/appointments/{$appt->id}/status", ['status' => 'in_room'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appt->id, 'status' => 'in_room']);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $appt = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->patch("/appointments/{$appt->id}/status", ['status' => 'invalid_status'])
            ->assertSessionHasErrors('status');
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function test_can_delete_appointment(): void
    {
        $appt = Appointment::factory()->create([
            'patient_id' => $this->patient->id,
            'user_id'    => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->delete("/appointments/{$appt->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('appointments', ['id' => $appt->id]);
    }
}
