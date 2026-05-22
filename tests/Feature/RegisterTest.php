<?php

namespace Tests\Feature;

use App\Models\LookupCategory;
use App\Models\LookupValue;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ── Access ───────────────────────────────────────────────────────────────

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/register-patient')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_register_page(): void
    {
        $this->actingAs($this->user)->get('/register-patient')->assertOk();
    }

    // ── Lookup data is passed ─────────────────────────────────────────────────

    public function test_register_page_receives_lookups_prop(): void
    {
        $cat = LookupCategory::create([
            'group'   => 'patient',
            'slug'    => 'bangsa',
            'name_ms' => 'Bangsa',
            'name_en' => 'Race',
        ]);

        LookupValue::create([
            'category_id' => $cat->id,
            'code'        => 'melayu',
            'label_ms'    => 'Melayu',
            'label_en'    => 'Malay',
            'is_active'   => true,
        ]);

        $response = $this->actingAs($this->user)->get('/register-patient');
        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Register')
                ->has('lookups')
        );
    }

    public function test_register_page_passes_bangsa_lookup(): void
    {
        $cat = LookupCategory::create([
            'group'   => 'patient',
            'slug'    => 'bangsa',
            'name_ms' => 'Bangsa',
            'name_en' => 'Race',
        ]);

        LookupValue::create([
            'category_id' => $cat->id,
            'code'        => 'melayu',
            'label_ms'    => 'Melayu',
            'label_en'    => 'Malay',
            'is_active'   => true,
        ]);

        $response = $this->actingAs($this->user)->get('/register-patient');
        $response->assertInertia(
            fn ($page) => $page
                ->has('lookups.bangsa', 1)
                ->where('lookups.bangsa.0.code', 'melayu')
        );
    }

    public function test_inactive_lookup_values_are_excluded(): void
    {
        $cat = LookupCategory::create([
            'group'   => 'patient',
            'slug'    => 'bangsa',
            'name_ms' => 'Bangsa',
            'name_en' => 'Race',
        ]);

        LookupValue::create([
            'category_id' => $cat->id,
            'code'        => 'active_val',
            'label_ms'    => 'Aktif',
            'label_en'    => 'Active',
            'is_active'   => true,
        ]);

        LookupValue::create([
            'category_id' => $cat->id,
            'code'        => 'inactive_val',
            'label_ms'    => 'Tidak Aktif',
            'label_en'    => 'Inactive',
            'is_active'   => false,
        ]);

        $response = $this->actingAs($this->user)->get('/register-patient');
        $response->assertInertia(
            fn ($page) => $page->has('lookups.bangsa', 1)
        );
    }

    // ── Form submission ───────────────────────────────────────────────────────

    public function test_can_register_patient_via_register_page(): void
    {
        $this->actingAs($this->user)
            ->post('/patients', [
                'name'          => 'Maryam binti Osman',
                'ic_number'     => '920303-12-3456',
                'date_of_birth' => '1992-03-03',
                'gender'        => 'female',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patients', ['ic_number' => '920303-12-3456']);
    }

    public function test_register_patient_with_race_field(): void
    {
        $this->actingAs($this->user)
            ->post('/patients', [
                'name'          => 'Ahmad bin Kassim',
                'ic_number'     => '880505-14-7890',
                'date_of_birth' => '1988-05-05',
                'gender'        => 'male',
                'race'          => 'melayu',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('patients', [
            'ic_number' => '880505-14-7890',
            'race'      => 'melayu',
        ]);
    }

    public function test_register_page_current_route_is_register(): void
    {
        $response = $this->actingAs($this->user)->get('/register-patient');
        $response->assertInertia(
            fn ($page) => $page->where('currentRoute', 'register')
        );
    }
}
