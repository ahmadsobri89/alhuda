<?php

namespace Tests\Unit;

use App\Models\MedicalCertificate;
use App\Models\Patient;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalCertificateModelTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = Patient::factory()->create();
        $this->visit   = Visit::factory()->create(['patient_id' => $this->patient->id]);
    }

    private function makeMC(array $overrides = []): MedicalCertificate
    {
        return MedicalCertificate::create(array_merge([
            'patient_id' => $this->patient->id,
            'visit_id'   => $this->visit->id,
            'issued_by'  => 'Dr. Test',
            'issue_date' => now()->toDateString(),
            'start_date' => '2025-01-10',
            'end_date'   => '2025-01-12',
            'days'       => 3,
        ], $overrides));
    }

    // ── Auto-number ───────────────────────────────────────────────────────────

    public function test_mc_number_is_auto_generated_on_create(): void
    {
        $mc = $this->makeMC();

        $this->assertNotNull($mc->mc_number);
        $this->assertMatchesRegularExpression('/^MC-\d{4}-\d{4}$/', $mc->mc_number);
    }

    public function test_mc_number_includes_current_year(): void
    {
        $mc   = $this->makeMC();
        $year = now()->year;

        $this->assertStringContainsString("MC-{$year}-", $mc->mc_number);
    }

    public function test_mc_numbers_are_sequential(): void
    {
        $first  = $this->makeMC();
        $second = $this->makeMC();

        $year = now()->year;
        $this->assertEquals("MC-{$year}-0001", $first->mc_number);
        $this->assertEquals("MC-{$year}-0002", $second->mc_number);
    }

    public function test_explicit_mc_number_is_not_overwritten(): void
    {
        $mc = $this->makeMC(['mc_number' => 'MC-CUSTOM-9999']);

        $this->assertEquals('MC-CUSTOM-9999', $mc->mc_number);
    }

    // ── Date casts ────────────────────────────────────────────────────────────

    public function test_start_date_is_cast_to_date(): void
    {
        $mc = $this->makeMC(['start_date' => '2025-06-01']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $mc->fresh()->start_date);
        $this->assertEquals('2025-06-01', $mc->fresh()->start_date->format('Y-m-d'));
    }

    public function test_end_date_is_cast_to_date(): void
    {
        $mc = $this->makeMC(['end_date' => '2025-06-03']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $mc->fresh()->end_date);
        $this->assertEquals('2025-06-03', $mc->fresh()->end_date->format('Y-m-d'));
    }

    public function test_issue_date_is_cast_to_date(): void
    {
        $mc = $this->makeMC(['issue_date' => '2025-06-01']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $mc->fresh()->issue_date);
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function test_mc_belongs_to_patient(): void
    {
        $mc = $this->makeMC();

        $this->assertInstanceOf(Patient::class, $mc->patient);
        $this->assertEquals($this->patient->id, $mc->patient->id);
    }

    public function test_mc_belongs_to_visit(): void
    {
        $mc = $this->makeMC();

        $this->assertInstanceOf(Visit::class, $mc->visit);
        $this->assertEquals($this->visit->id, $mc->visit->id);
    }
}
