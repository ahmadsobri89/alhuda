<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── Access ──────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/patients')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_patients_list(): void
    {
        $this->actingAs($this->user)->get('/patients')->assertOk();
    }

    public function test_patients_list_supports_search_filter(): void
    {
        Patient::factory()->create(['name' => 'Ahmad Zaki']);
        Patient::factory()->create(['name' => 'Siti Aminah']);

        $this->actingAs($this->user)
            ->get('/patients?search=Ahmad')
            ->assertOk();
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_can_register_new_patient(): void
    {
        $data = [
            'name'         => 'Ahmad bin Ali',
            'ic_number'    => '900101-14-5555',
            'date_of_birth'=> '1990-01-01',
            'gender'       => 'male',
        ];

        $this->actingAs($this->user)
            ->post('/patients', $data)
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patients', ['ic_number' => '900101-14-5555']);
    }

    public function test_patient_id_is_auto_generated_on_register(): void
    {
        $this->actingAs($this->user)->post('/patients', [
            'name'         => 'Fatimah binti Yusof',
            'ic_number'    => '850202-12-6666',
            'date_of_birth'=> '1985-02-02',
            'gender'       => 'female',
        ]);

        $patient = Patient::where('ic_number', '850202-12-6666')->first();
        $this->assertNotNull($patient->patient_id);
        $this->assertMatchesRegularExpression('/^P-\d{4}-\d{5}$/', $patient->patient_id);
    }

    public function test_cannot_register_patient_with_duplicate_ic_number(): void
    {
        Patient::factory()->create(['ic_number' => '900101-14-5555']);

        $this->actingAs($this->user)
            ->post('/patients', [
                'name'         => 'Another Person',
                'ic_number'    => '900101-14-5555',
                'date_of_birth'=> '1990-01-01',
                'gender'       => 'male',
            ])
            ->assertSessionHasErrors('ic_number');
    }

    public function test_cannot_register_patient_with_future_date_of_birth(): void
    {
        $this->actingAs($this->user)
            ->post('/patients', [
                'name'         => 'Future Baby',
                'ic_number'    => '300101-14-9999',
                'date_of_birth'=> now()->addDay()->format('Y-m-d'),
                'gender'       => 'male',
            ])
            ->assertSessionHasErrors('date_of_birth');
    }

    public function test_cannot_register_patient_with_invalid_gender(): void
    {
        $this->actingAs($this->user)
            ->post('/patients', [
                'name'         => 'Test Patient',
                'ic_number'    => '900101-14-5556',
                'date_of_birth'=> '1990-01-01',
                'gender'       => 'unknown',
            ])
            ->assertSessionHasErrors('gender');
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->user)
            ->post('/patients', [
                'ic_number'    => '900101-14-5557',
                'date_of_birth'=> '1990-01-01',
                'gender'       => 'male',
            ])
            ->assertSessionHasErrors('name');
    }

    // ── Update ───────────────────────────────────────────────────────────────

    public function test_can_update_patient_details(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->user)
            ->put("/patients/{$patient->id}", [
                'name'         => 'Updated Name',
                'ic_number'    => $patient->ic_number,
                'date_of_birth'=> $patient->date_of_birth->format('Y-m-d'),
                'gender'       => $patient->gender,
                'phone'        => '012-3456789',
                'status'       => 'active',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'phone' => '012-3456789']);
    }

    public function test_cannot_update_patient_with_duplicate_ic_number(): void
    {
        $patientA = Patient::factory()->create(['ic_number' => '800101-01-1111']);
        $patientB = Patient::factory()->create(['ic_number' => '800202-02-2222']);

        $this->actingAs($this->user)
            ->put("/patients/{$patientB->id}", [
                'name'         => $patientB->name,
                'ic_number'    => '800101-01-1111',
                'date_of_birth'=> $patientB->date_of_birth->format('Y-m-d'),
                'gender'       => $patientB->gender,
            ])
            ->assertSessionHasErrors('ic_number');
    }

    // ── Destroy ──────────────────────────────────────────────────────────────

    public function test_can_delete_patient(): void
    {
        $patient = Patient::factory()->create();

        $this->actingAs($this->user)
            ->delete("/patients/{$patient->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('patients', ['id' => $patient->id]);
    }
}
