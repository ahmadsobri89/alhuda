<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/reports')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_reports(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/reports')->assertOk();
    }

    public function test_reports_with_month_period(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/reports?period=month')->assertOk();
    }

    public function test_reports_with_last_month_period(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/reports?period=last_month')->assertOk();
    }

    public function test_reports_with_year_period(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/reports?period=year')->assertOk();
    }
}
