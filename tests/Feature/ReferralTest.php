<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\ReferralLetter;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Patient $patient;
    private Visit $visit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user    = User::factory()->create();
        $this->patient = Patient::factory()->create();
        $this->visit   = Visit::factory()->create([
            'patient_id'  => $this->patient->id,
            'user_id'     => $this->user->id,
            'doctor_name' => 'Dr. Siti',
        ]);
    }

    private function validReferralData(array $overrides = []): array
    {
        return array_merge([
            'referred_to'      => 'Hospital Kuala Lumpur',
            'referred_to_dept' => 'Orthopaedics',
            'urgency'          => 'routine',
            'reason'           => 'Persistent knee pain for 3 months, unresponsive to conservative treatment.',
            'clinical_summary' => 'Patient presents with right knee pain, grade 2 OA on X-ray.',
            'relevant_history' => 'No previous surgeries. HTN controlled.',
        ], $overrides);
    }

    // ── Store ────────────────────────────────────────────────────────────────

    public function test_can_issue_referral_letter(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/referral", $this->validReferralData())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('referral_letters', [
            'visit_id'    => $this->visit->id,
            'patient_id'  => $this->patient->id,
            'referred_to' => 'Hospital Kuala Lumpur',
            'urgency'     => 'routine',
            'issued_by'   => 'Dr. Siti',
        ]);
    }

    public function test_ref_number_is_auto_generated(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/referral", $this->validReferralData());

        $ref = ReferralLetter::where('visit_id', $this->visit->id)->first();
        $this->assertNotNull($ref->ref_number);
        $this->assertMatchesRegularExpression('/^REF-\d{4}-\d{4}$/', $ref->ref_number);
    }

    public function test_referred_to_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/referral", $this->validReferralData(['referred_to' => '']))
            ->assertSessionHasErrors('referred_to');
    }

    public function test_reason_is_required(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/referral", $this->validReferralData(['reason' => '']))
            ->assertSessionHasErrors('reason');
    }

    public function test_urgency_is_required(): void
    {
        $data = $this->validReferralData();
        unset($data['urgency']);

        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/referral", $data)
            ->assertSessionHasErrors('urgency');
    }

    public function test_urgency_must_be_routine_urgent_or_emergency(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/referral", $this->validReferralData(['urgency' => 'critical']))
            ->assertSessionHasErrors('urgency');
    }

    public function test_urgency_accepts_all_valid_values(): void
    {
        foreach (['routine', 'urgent', 'emergency'] as $urgency) {
            $this->actingAs($this->user)
                ->post("/emr/{$this->visit->id}/referral", $this->validReferralData(['urgency' => $urgency]))
                ->assertSessionHas('success');
        }

        $this->assertEquals(3, ReferralLetter::where('visit_id', $this->visit->id)->count());
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/referral", [
                'referred_to' => 'Hospital Selayang',
                'urgency'     => 'urgent',
                'reason'      => 'Follow-up required.',
            ])
            ->assertSessionHas('success');
    }

    public function test_guests_cannot_issue_referral(): void
    {
        $this->post("/emr/{$this->visit->id}/referral", $this->validReferralData())
            ->assertRedirect('/login');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_can_delete_referral_letter(): void
    {
        $ref = ReferralLetter::create([
            'patient_id'  => $this->patient->id,
            'visit_id'    => $this->visit->id,
            'issued_by'   => 'Dr. Siti',
            'issue_date'  => now()->toDateString(),
            'referred_to' => 'Hospital Ampang',
            'urgency'     => 'routine',
            'reason'      => 'Specialist follow-up.',
        ]);

        $this->actingAs($this->user)
            ->delete("/referral/{$ref->id}")
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('referral_letters', ['id' => $ref->id]);
    }

    public function test_guests_cannot_delete_referral(): void
    {
        $ref = ReferralLetter::create([
            'patient_id'  => $this->patient->id,
            'visit_id'    => $this->visit->id,
            'issued_by'   => 'Dr. Siti',
            'issue_date'  => now()->toDateString(),
            'referred_to' => 'Hospital Ampang',
            'urgency'     => 'routine',
            'reason'      => 'Test.',
        ]);

        $this->delete("/referral/{$ref->id}")->assertRedirect('/login');
    }

    // ── Sequential numbering ─────────────────────────────────────────────────

    public function test_ref_numbers_are_sequential(): void
    {
        $this->actingAs($this->user)
            ->post("/emr/{$this->visit->id}/referral", $this->validReferralData());

        $visit2 = Visit::factory()->create(['patient_id' => $this->patient->id, 'user_id' => $this->user->id]);
        $this->actingAs($this->user)
            ->post("/emr/{$visit2->id}/referral", $this->validReferralData());

        $year = now()->year;
        $refs = ReferralLetter::orderBy('id')->get();
        $this->assertEquals("REF-{$year}-0001", $refs[0]->ref_number);
        $this->assertEquals("REF-{$year}-0002", $refs[1]->ref_number);
    }
}
