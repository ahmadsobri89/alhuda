<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\ReferralLetter;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralLetterModelTest extends TestCase
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

    private function makeReferral(array $overrides = []): ReferralLetter
    {
        return ReferralLetter::create(array_merge([
            'patient_id'  => $this->patient->id,
            'visit_id'    => $this->visit->id,
            'issued_by'   => 'Dr. Test',
            'issue_date'  => now()->toDateString(),
            'referred_to' => 'Hospital KL',
            'urgency'     => 'routine',
            'reason'      => 'Follow-up required.',
        ], $overrides));
    }

    // ── Auto-number ───────────────────────────────────────────────────────────

    public function test_ref_number_is_auto_generated_on_create(): void
    {
        $ref = $this->makeReferral();

        $this->assertNotNull($ref->ref_number);
        $this->assertMatchesRegularExpression('/^REF-\d{4}-\d{4}$/', $ref->ref_number);
    }

    public function test_ref_number_includes_current_year(): void
    {
        $ref  = $this->makeReferral();
        $year = now()->year;

        $this->assertStringContainsString("REF-{$year}-", $ref->ref_number);
    }

    public function test_ref_numbers_are_sequential(): void
    {
        $first  = $this->makeReferral();
        $second = $this->makeReferral();

        $year = now()->year;
        $this->assertEquals("REF-{$year}-0001", $first->ref_number);
        $this->assertEquals("REF-{$year}-0002", $second->ref_number);
    }

    public function test_explicit_ref_number_is_not_overwritten(): void
    {
        $ref = $this->makeReferral(['ref_number' => 'REF-CUSTOM-0001']);

        $this->assertEquals('REF-CUSTOM-0001', $ref->ref_number);
    }

    // ── Date cast ─────────────────────────────────────────────────────────────

    public function test_issue_date_is_cast_to_date(): void
    {
        $ref = $this->makeReferral(['issue_date' => '2025-07-01']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $ref->fresh()->issue_date);
        $this->assertEquals('2025-07-01', $ref->fresh()->issue_date->format('Y-m-d'));
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function test_referral_belongs_to_patient(): void
    {
        $ref = $this->makeReferral();

        $this->assertInstanceOf(Patient::class, $ref->patient);
        $this->assertEquals($this->patient->id, $ref->patient->id);
    }

    public function test_referral_belongs_to_visit(): void
    {
        $ref = $this->makeReferral();

        $this->assertInstanceOf(Visit::class, $ref->visit);
        $this->assertEquals($this->visit->id, $ref->visit->id);
    }
}
