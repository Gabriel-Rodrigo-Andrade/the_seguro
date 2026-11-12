<?php

namespace Tests\Feature;

use App\Models\InsuranceContract;
use App\Models\Proposal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-12 12:00:00', 'America/Sao_Paulo'));
        $this->seed(DatabaseSeeder::class);
    }

    private function login(string $email): void
    {
        $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    public function test_customer_counts_match_owned_records_and_do_not_leak_other_customer(): void
    {
        $this->login('cliente1@theseguro.test');
        $response = $this->get('/dashboard')->assertOk()->assertSee('ABC1D23')->assertDontSee('DEF5678');
        $counts = $response->viewData('counts');
        $this->assertSame(3, $counts['proposals']);
        $this->assertSame(1, $counts['contracts']);
        $this->assertSame(0, $counts['expiring']);
        $this->assertSame(1, $counts['open']);
        $this->assertSame(1, $counts['in_progress']);
        $this->assertCount(3, $response->viewData('proposals'));
        $this->assertCount(2, $response->viewData('assistance'));
        $this->login('cliente2@theseguro.test');
        $response = $this->get('/dashboard')->assertOk()->assertDontSee('ABC1D23');
        $this->assertSame(0, $response->viewData('counts')['contracts']);
        $this->assertSame(0, $response->viewData('counts')['open']);
    }

    public function test_empty_customer_has_zero_totals_and_empty_states(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->get('/dashboard')->assertOk()->assertSee('Nenhuma proposta neste filtro.')->assertSee('Nenhum contrato neste filtro.')->assertDontSee('ABC1D23');
        $this->assertSame([0], array_values(array_unique(array_values($response->viewData('counts')))));
    }

    public function test_broker_sees_only_operational_queues_even_with_forged_filters(): void
    {
        $this->login('corretor@theseguro.test');
        $response = $this->get('/dashboard')->assertOk();
        $this->assertSame(1, $response->viewData('counts')['proposals']);
        $this->assertSame(2, $response->viewData('counts')['assistance']);
        $this->assertSame(['submitted'], $response->viewData('proposals')->pluck('status')->map(fn ($status) => $status->value)->all());
        $response = $this->get('/dashboard?proposal_status=draft&assistance_status=completed')->assertOk();
        $this->assertSame(0, $response->viewData('counts')['proposals']);
        $this->assertSame(0, $response->viewData('counts')['assistance']);
    }

    public function test_admin_totals_and_independent_status_filters(): void
    {
        $this->login('admin@theseguro.test');
        $response = $this->get('/dashboard')->assertOk();
        $counts = $response->viewData('counts');
        $this->assertSame(4, $counts['users']);
        $this->assertSame(2, $counts['vehicles']);
        $this->assertSame(6, $counts['proposals']);
        $this->assertSame(1, $counts['contracts']);
        $this->assertSame(3, $counts['assistance']);
        $this->assertCount(5, $response->viewData('proposals'));
        $filtered = $this->get('/dashboard?proposal_status=approved&assistance_status=completed')->assertOk()->viewData('counts');
        $this->assertSame(1, $filtered['proposals']);
        $this->assertSame(1, $filtered['assistance']);
        $this->assertSame(0, $filtered['open']);
        $this->assertSame(1, $filtered['contracts']);
    }

    public function test_expiring_window_includes_day_30_but_excludes_expired_future_and_day_31(): void
    {
        $this->login('cliente1@theseguro.test');
        $source = InsuranceContract::firstOrFail();
        foreach ([0, 1, 30, 31] as $days) {
            $proposal = $source->proposal->replicate();
            $proposal->save();
            InsuranceContract::create(['proposal_id' => $proposal->id, 'contract_number' => 'BOUNDARY-'.$days, 'contracted_at' => now(), 'starts_on' => '2026-01-01', 'ends_on' => now('America/Sao_Paulo')->addDays($days)->toDateString()]);
        }
        $proposal = $source->proposal->replicate();
        $proposal->save();
        InsuranceContract::create(['proposal_id' => $proposal->id, 'contract_number' => 'FUTURE', 'contracted_at' => now(), 'starts_on' => '2026-11-13', 'ends_on' => '2026-12-01']);
        $response = $this->get('/dashboard')->assertOk();
        $this->assertSame(4, $response->viewData('counts')['contracts']);
        $this->assertSame(2, $response->viewData('counts')['expiring']);
        $this->assertSame(['BOUNDARY-1', 'BOUNDARY-30'], $response->viewData('expiring')->pluck('contract_number')->all());
    }

    public function test_date_period_is_inclusive_in_sao_paulo_and_converts_to_utc(): void
    {
        $this->login('admin@theseguro.test');
        $draft = Proposal::where('status', 'draft')->firstOrFail();
        $draft->update(['created_at' => '2026-11-12 02:59:59']);
        foreach (['2026-11-12 03:00:00', '2026-11-13 02:59:59', '2026-11-13 03:00:00'] as $createdAt) {
            $copy = $draft->replicate();
            $copy->created_at = $createdAt;
            $copy->save();
        }
        $response = $this->get('/dashboard?date_from=2026-11-12&date_to=2026-11-12&proposal_status=draft')->assertOk();
        $this->assertSame(2, $response->viewData('counts')['proposals']);
        $this->assertSame(0, $response->viewData('counts')['contracts']);
        $this->assertSame(0, $response->viewData('counts')['assistance']);
        $this->get('/dashboard?date_to=2026-11-12')->assertOk();
    }

    public function test_invalid_filters_are_rejected_and_guest_is_redirected(): void
    {
        $this->login('admin@theseguro.test');
        $this->get('/dashboard?date_from=2026-11-12&date_to=2026-11-11')->assertSessionHasErrors('date_to');
        $this->get('/dashboard?date_from=invalid&proposal_status=invalid&assistance_status=invalid')->assertSessionHasErrors(['date_from', 'proposal_status', 'assistance_status']);
        $this->post('/logout');
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_query_count_does_not_grow_with_list_size(): void
    {
        $this->login('admin@theseguro.test');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get('/dashboard')->assertOk();
        $before = count(DB::getQueryLog());
        $source = Proposal::firstOrFail();
        for ($i = 0; $i < 15; $i++) {
            $copy = $source->replicate();
            $copy->save();
        }
        DB::flushQueryLog();
        $response = $this->get('/dashboard')->assertOk();
        $after = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame($before, $after);
        $this->assertSame(21, $response->viewData('counts')['proposals']);
        $this->assertCount(5, $response->viewData('proposals'));
    }
}
