<?php

namespace Tests\Unit;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_age_attribute_returns_correct_value(): void
    {
        $patient = Patient::factory()->create([
            'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
        ]);

        $this->assertEquals(30, $patient->age);
    }

    public function test_age_gender_attribute_for_male(): void
    {
        $patient = Patient::factory()->create([
            'date_of_birth' => now()->subYears(25)->format('Y-m-d'),
            'gender'        => 'male',
        ]);

        $this->assertEquals('25L', $patient->age_gender);
    }

    public function test_age_gender_attribute_for_female(): void
    {
        $patient = Patient::factory()->create([
            'date_of_birth' => now()->subYears(40)->format('Y-m-d'),
            'gender'        => 'female',
        ]);

        $this->assertEquals('40P', $patient->age_gender);
    }

    public function test_patient_id_is_auto_generated_on_create(): void
    {
        $patient = Patient::factory()->create();

        $this->assertNotNull($patient->patient_id);
        $this->assertMatchesRegularExpression('/^P-\d{4}-\d{5}$/', $patient->patient_id);
    }

    public function test_patient_id_increments_sequentially(): void
    {
        $first  = Patient::factory()->create();
        $second = Patient::factory()->create();

        $year = now()->year;
        $this->assertEquals("P-{$year}-00001", $first->patient_id);
        $this->assertEquals("P-{$year}-00002", $second->patient_id);
    }

    public function test_conditions_cast_to_array(): void
    {
        $patient = Patient::factory()->create([
            'conditions' => ['Diabetes', 'Hypertension'],
        ]);

        $this->assertIsArray($patient->fresh()->conditions);
        $this->assertContains('Diabetes', $patient->fresh()->conditions);
    }

    public function test_explicit_patient_id_is_not_overwritten(): void
    {
        $patient = Patient::factory()->create(['patient_id' => 'P-CUSTOM-001']);

        $this->assertEquals('P-CUSTOM-001', $patient->patient_id);
    }
}
