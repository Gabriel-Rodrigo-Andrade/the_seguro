<?php

namespace Tests\Feature;

use App\Actions\Contracts\ConfirmProposal;
use App\Enums\UserRole;
use App\Models\InsuranceContract;
use App\Models\Proposal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\InsuranceContractSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\ProposalReviewSeeder;
use Database\Seeders\ProposalSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\VehicleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InsuranceContractTest extends TestCase
{
    use RefreshDatabase;

    private Proposal $proposal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, VehicleSeeder::class, ServiceSeeder::class, PlanSeeder::class, ProposalSeeder::class, ProposalReviewSeeder::class]);
        $this->proposal = Proposal::where('status', 'approved')->firstOrFail();
        $this->actingAs($this->proposal->customer);
    }

    private function confirm(): InsuranceContract
    {
        $this->post('/proposals/'.$this->proposal->id.'/confirm', ['contract_number' => 'FORGED', 'starts_on' => '2000-01-01', 'ends_on' => '2000-01-02'])->assertSessionHasNoErrors()->assertRedirect();

        return InsuranceContract::where('proposal_id', $this->proposal->id)->firstOrFail();
    }

    public function test_confirmation_is_idempotent_and_fields_are_server_owned(): void
    {
        $snapshot = $this->proposal->services_snapshot;
        $contract = $this->confirm();
        $this->assertStringStartsWith('TS-', $contract->contract_number);
        $this->assertSame(now('America/Sao_Paulo')->toDateString(), $contract->starts_on->toDateString());
        $this->assertSame($contract->starts_on->addMonthsNoOverflow(12)->toDateString(), $contract->ends_on->toDateString());
        $this->assertDatabaseHas('proposals', ['id' => $this->proposal->id, 'status' => 'contracted']);
        $again = app(ConfirmProposal::class)->handle($this->proposal->customer, $this->proposal);
        $this->assertSame($contract->id, $again->id);
        $this->confirm();
        $this->assertDatabaseCount('insurance_contracts', 1);
        $this->proposal->plan->update(['annual_price' => '9999.00']);
        $this->proposal->plan->services()->first()->update(['description' => 'Alterada depois']);
        $this->get('/contracts/'.$contract->id)->assertOk()->assertSee($snapshot[0]['description'])->assertDontSee('Alterada depois')->assertSee('960,00');
        $this->get('/contracts')->assertOk()->assertSee($contract->contract_number);
        $this->assertSame($snapshot, $this->proposal->fresh()->services_snapshot);
    }

    public function test_only_owner_can_confirm_and_other_customer_cannot_read_contract(): void
    {
        $contract = $this->confirm();
        $this->actingAs(User::factory()->create());
        $this->get('/contracts')->assertOk()->assertDontSee($contract->contract_number);
        $this->get('/contracts/'.$contract->id)->assertForbidden();
        foreach ([UserRole::Customer, UserRole::Broker, UserRole::Administrator] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->post('/proposals/'.$this->proposal->id.'/confirm')->assertForbidden();
        }
        $this->get('/contracts/'.$contract->id)->assertOk();
        $this->assertDatabaseCount('insurance_contracts', 1);
    }

    public function test_non_approved_proposals_cannot_be_confirmed(): void
    {
        foreach (Proposal::whereIn('status', ['draft', 'submitted', 'rejected'])->get() as $proposal) {
            $this->actingAs($proposal->customer)->post('/proposals/'.$proposal->id.'/confirm')->assertForbidden();
        }
        $this->assertDatabaseCount('insurance_contracts', 0);
    }

    public function test_leap_year_and_exclusive_end_use_sao_paulo_dates(): void
    {
        $this->travelTo(CarbonImmutable::parse('2024-03-01 01:00:00', 'UTC'));
        $contract = $this->confirm();
        $this->assertSame('2024-02-29', $contract->starts_on->toDateString());
        $this->assertSame('2025-02-28', $contract->ends_on->toDateString());
        $this->assertFalse($contract->isActiveAt(CarbonImmutable::parse('2024-02-28 23:59:59', 'America/Sao_Paulo')));
        $this->assertTrue($contract->isActiveAt(CarbonImmutable::parse('2024-02-29 00:00:00', 'America/Sao_Paulo')));
        $this->assertTrue($contract->isActiveAt(CarbonImmutable::parse('2025-02-28 02:59:59', 'UTC')));
        $this->assertFalse($contract->isActiveAt(CarbonImmutable::parse('2025-02-28 03:00:00', 'UTC')));
        $this->get('/contracts/'.$contract->id)->assertOk()->assertSee('27/02/2025')->assertSee('28/02/2025');
    }

    public function test_failure_after_contract_insert_rolls_back_both_changes(): void
    {
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'update "proposals"')) {
                throw new \RuntimeException('Falha simulada');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->post('/proposals/'.$this->proposal->id.'/confirm');
            $this->fail('Deveria falhar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falha simulada', $exception->getMessage());
        }
        $this->assertDatabaseCount('insurance_contracts', 0);
        $this->assertDatabaseHas('proposals', ['id' => $this->proposal->id, 'status' => 'approved']);
    }

    public function test_database_rejects_duplicates_and_invalid_period(): void
    {
        $contract = $this->confirm();
        $attributes = $contract->only(['proposal_id', 'contract_number', 'contracted_at', 'starts_on', 'ends_on']);
        foreach ([['23505', [...$attributes, 'contract_number' => 'TS-OTHER']], ['23514', [...$attributes, 'starts_on' => '2026-01-01', 'ends_on' => '2026-01-01']]] as [$state, $data]) {
            try {
                DB::transaction(fn () => DB::table('insurance_contracts')->insert($data));
                $this->fail('O banco deveria rejeitar.');
            } catch (QueryException $exception) {
                $this->assertSame($state, $exception->errorInfo[0]);
            }
        }
        $this->assertDatabaseCount('insurance_contracts', 1);
    }

    public function test_no_edit_delete_routes_and_inactive_or_guest_access(): void
    {
        $contract = $this->confirm();
        $this->put('/contracts/'.$contract->id, [])->assertStatus(405);
        $this->delete('/contracts/'.$contract->id)->assertStatus(405);
        $owner = $this->proposal->customer;
        $owner->forceFill(['is_active' => false])->save();
        $this->actingAs($owner)->post('/proposals/'.$this->proposal->id.'/confirm')->assertRedirect('/login');
        $this->get('/contracts')->assertRedirect('/login');
    }

    public function test_seed_creates_current_and_expired_contracts_without_changing_previous_proposals(): void
    {
        $before = Proposal::orderBy('id')->get()->map->getAttributes()->all();
        $this->seed(InsuranceContractSeeder::class);
        $this->assertDatabaseCount('insurance_contracts', 2);
        $this->assertSame($before, Proposal::orderBy('id')->limit(4)->get()->map->getAttributes()->all());
        $contracts = InsuranceContract::orderBy('id')->get();
        $this->assertTrue($contracts[0]->isActiveAt());
        $this->assertFalse($contracts[1]->isActiveAt());
        foreach ($contracts as $contract) {
            $this->assertSame('contracted', $contract->proposal->status->value);
        }
        $this->seed(InsuranceContractSeeder::class);
        $this->assertDatabaseCount('insurance_contracts', 2);
        $this->assertDatabaseCount('proposals', 6);
    }
}
