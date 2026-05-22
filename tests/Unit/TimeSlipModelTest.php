<?php

namespace Tests\Unit;

use App\Models\Patient;
use App\Models\TimeSlip;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSlipModelTest extends TestCase
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

    private function makeSlip(array $overrides = []): TimeSlip
    {
        return TimeSlip::create(array_merge([
            'patient_id'     => $this->patient->id,
            'visit_id'       => $this->visit->id,
            'issued_by'      => 'Dr. Test',
            'slip_date'      => now()->toDateString(),
            'arrival_time'   => '09:00',
            'departure_time' => '10:00',
        ], $overrides));
    }

    // ── Auto-number ───────────────────────────────────────────────────────────

    public function test_slip_number_is_auto_generated_on_create(): void
    {
        $slip = $this->makeSlip();

        $this->assertNotNull($slip->slip_number);
        $this->assertMatchesRegularExpression('/^TS-\d{4}-\d{4}$/', $slip->slip_number);
    }

    public function test_slip_number_includes_current_year(): void
    {
        $slip = $this->makeSlip();
        $year = now()->year;

        $this->assertStringContainsString("TS-{$year}-", $slip->slip_number);
    }

    public function test_slip_numbers_are_sequential(): void
    {
        $first  = $this->makeSlip();
        $second = $this->makeSlip();

        $year = now()->year;
        $this->assertEquals("TS-{$year}-0001", $first->slip_number);
        $this->assertEquals("TS-{$year}-0002", $second->slip_number);
    }

    public function test_explicit_slip_number_is_not_overwritten(): void
    {
        $slip = $this->makeSlip(['slip_number' => 'TS-CUSTOM-0001']);

        $this->assertEquals('TS-CUSTOM-0001', $slip->slip_number);
    }

    // ── durationMinutes() ─────────────────────────────────────────────────────

    public function test_duration_minutes_calculates_correctly(): void
    {
        $slip = $this->makeSlip(['arrival_time' => '09:00', 'departure_time' => '10:00']);

        $this->assertEquals(60, $slip->durationMinutes());
    }

    public function test_duration_minutes_handles_partial_hours(): void
    {
        $slip = $this->makeSlip(['arrival_time' => '09:15', 'departure_time' => '09:45']);

        $this->assertEquals(30, $slip->durationMinutes());
    }

    public function test_duration_minutes_spans_multiple_hours(): void
    {
        $slip = $this->makeSlip(['arrival_time' => '08:00', 'departure_time' => '11:30']);

        $this->assertEquals(210, $slip->durationMinutes());
    }

    public function test_duration_minutes_for_one_minute(): void
    {
        $slip = $this->makeSlip(['arrival_time' => '09:00', 'departure_time' => '09:01']);

        $this->assertEquals(1, $slip->durationMinutes());
    }

    // ── Date cast ─────────────────────────────────────────────────────────────

    public function test_slip_date_is_cast_to_date(): void
    {
        $slip = $this->makeSlip(['slip_date' => '2025-08-01']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $slip->fresh()->slip_date);
        $this->assertEquals('2025-08-01', $slip->fresh()->slip_date->format('Y-m-d'));
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    public function test_slip_belongs_to_patient(): void
    {
        $slip = $this->makeSlip();

        $this->assertInstanceOf(Patient::class, $slip->patient);
        $this->assertEquals($this->patient->id, $slip->patient->id);
    }

    public function test_slip_belongs_to_visit(): void
    {
        $slip = $this->makeSlip();

        $this->assertInstanceOf(Visit::class, $slip->visit);
        $this->assertEquals($this->visit->id, $slip->visit->id);
    }
}
