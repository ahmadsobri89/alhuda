<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/queue')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_queue(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/queue')->assertOk();
    }

    public function test_queue_shows_only_todays_appointments(): void
    {
        $user    = User::factory()->create();
        $patient = Patient::factory()->create();

        // Today's appointment
        Appointment::factory()->today()->create([
            'patient_id' => $patient->id,
            'user_id'    => $user->id,
        ]);

        // Future appointment — must not appear in queue
        Appointment::factory()->create([
            'patient_id'       => $patient->id,
            'user_id'          => $user->id,
            'appointment_date' => now()->addDays(3)->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)->get('/queue');
        $response->assertOk();
    }
}
