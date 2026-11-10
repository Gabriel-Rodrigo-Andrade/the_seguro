<?php

namespace Tests\Feature;

use App\Actions\Proposals\SubmitProposal;
use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\Proposal;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ProposalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProposalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    private Vehicle $vehicle;

    private Plan $plan;

    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create();
        $this->vehicle = $this->customer->vehicles()->create(['license_plate' => 'XYZ1A23', 'brand' => 'Marca', 'model' => 'Modelo', 'manufacture_year' => 2020]);
        $this->service = Service::create(['name' => 'Guincho', 'description' => 'Descrição original', 'is_active' => true]);
        $this->plan = Plan::create(['name' => 'Teste', 'description' => 'Plano', 'annual_price' => '123.45', 'is_active' => true]);
        $this->plan->services()->attach($this->service);
        $this->actingAs($this->customer);
    }

    private function draft(): Proposal
    {
        $this->post('/proposals', ['vehicle_id' => $this->vehicle->id, 'plan_id' => $this->plan->id])->assertSessionHasNoErrors()->assertRedirect();

        return Proposal::latest('id')->firstOrFail();
    }

    private function token(Proposal $proposal): string
    {
        return app(SubmitProposal::class)->confirmationToken($proposal->fresh()->load('plan.services'));
    }

    private function submit(Proposal $proposal): void
    {
        $this->post('/proposals/'.$proposal->id.'/submit', ['quote_token' => $this->token($proposal)])->assertSessionHasNoErrors()->assertRedirect();
    }

    public function test_customer_crud_and_server_owned_values(): void
    {
        $this->get('/proposals/create')->assertOk()->assertSee('Guincho');
        $this->post('/proposals', ['vehicle_id' => $this->vehicle->id, 'plan_id' => $this->plan->id, 'user_id' => -1, 'quoted_amount' => '0.01', 'status' => 'approved', 'services_snapshot' => [['id' => -1]]])->assertSessionHasNoErrors();
        $proposal = Proposal::firstOrFail();
        $this->assertSame($this->customer->id, $proposal->user_id);
        $this->assertSame('123.45', $proposal->quoted_amount);
        $this->assertSame(ProposalStatus::Draft, $proposal->status);
        $this->assertNull($proposal->services_snapshot);
        $this->get('/proposals')->assertOk()->assertSee('XYZ1A23');
        $this->get('/proposals/'.$proposal->id)->assertOk()->assertSee('Confirmar cotação');
        $this->get('/proposals/'.$proposal->id.'/edit')->assertOk();
        $this->plan->update(['annual_price' => '200.00']);
        $this->put('/proposals/'.$proposal->id, ['vehicle_id' => $this->vehicle->id, 'plan_id' => $this->plan->id, 'quoted_amount' => '1.00'])->assertSessionHasNoErrors();
        $this->assertSame('200.00', $proposal->fresh()->quoted_amount);
        $this->delete('/proposals/'.$proposal->id)->assertRedirect('/proposals');
        $this->assertDatabaseCount('proposals', 0);
    }

    public function test_other_customer_cannot_access_or_mutate_proposal_or_use_foreign_vehicle(): void
    {
        $proposal = $this->draft();
        $token = $this->token($proposal);
        $this->actingAs(User::factory()->create());
        $this->get('/proposals')->assertOk()->assertDontSee('XYZ1A23');
        $this->get('/proposals/'.$proposal->id)->assertForbidden();
        $this->get('/proposals/'.$proposal->id.'/edit')->assertForbidden();
        $this->put('/proposals/'.$proposal->id, [])->assertForbidden();
        $this->delete('/proposals/'.$proposal->id)->assertForbidden();
        $this->post('/proposals/'.$proposal->id.'/submit', ['quote_token' => $token])->assertForbidden();
        $this->post('/proposals', ['vehicle_id' => $this->vehicle->id, 'plan_id' => $this->plan->id])->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('proposals', 1);
    }

    public function test_broker_only_reads_submitted_proposals_and_their_vehicles(): void
    {
        $proposal = $this->draft();
        $broker = User::factory()->create(['role' => UserRole::Broker]);
        $this->actingAs($broker)->get('/proposals')->assertOk()->assertDontSee('XYZ1A23');
        $this->get('/proposals/'.$proposal->id)->assertForbidden();
        $this->get('/vehicles/'.$this->vehicle->id)->assertForbidden();
        $this->post('/proposals', [])->assertForbidden();
        $this->actingAs($this->customer);
        $this->submit($proposal);
        $this->actingAs($broker)->get('/proposals/'.$proposal->id)->assertOk();
        $this->get('/vehicles')->assertOk()->assertSee('XYZ1A23');
        $this->get('/vehicles/'.$this->vehicle->id)->assertOk();
        $this->get('/vehicles/'.$this->vehicle->id.'/edit')->assertForbidden();
        $this->delete('/vehicles/'.$this->vehicle->id)->assertForbidden();
    }

    public function test_operator_cannot_send_on_behalf_of_customer(): void
    {
        $proposal = $this->draft();
        foreach ([UserRole::Administrator, UserRole::Broker] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->post('/proposals/'.$proposal->id.'/submit', ['quote_token' => $this->token($proposal)])->assertForbidden();
            $this->put('/proposals/'.$proposal->id, [])->assertForbidden();
            $this->delete('/proposals/'.$proposal->id)->assertForbidden();
        }
    }

    public function test_submit_freezes_amount_and_snapshot_and_cannot_repeat_or_edit(): void
    {
        $proposal = $this->draft();
        $this->submit($proposal);
        $submitted = $proposal->fresh();
        $this->assertSame(ProposalStatus::Submitted, $submitted->status);
        $this->assertNotNull($submitted->submitted_at);
        $this->assertSame('Descrição original', $submitted->services_snapshot[0]['description']);
        $this->plan->update(['annual_price' => '999.99']);
        $this->service->update(['description' => 'Nova descrição']);
        $this->get('/proposals/'.$proposal->id)->assertOk()->assertSee('Descrição original')->assertDontSee('Nova descrição')->assertSee('123,45');
        $this->assertSame('123.45', $proposal->fresh()->quoted_amount);
        $this->post('/proposals/'.$proposal->id.'/submit', ['quote_token' => $this->token($proposal)])->assertForbidden();
        $this->put('/proposals/'.$proposal->id, [])->assertForbidden();
        $this->delete('/proposals/'.$proposal->id)->assertForbidden();
        $this->assertEquals($submitted->submitted_at, $proposal->fresh()->submitted_at);
    }

    public function test_long_service_description_keeps_confirmation_compact(): void
    {
        $this->service->update(['description' => str_repeat('Texto ', 1500)]);
        $proposal = $this->draft();
        $this->assertLessThan(8192, strlen($this->token($proposal)));
        $this->submit($proposal);
        $this->assertSame($this->service->description, $proposal->fresh()->services_snapshot[0]['description']);
    }

    public function test_stale_price_requires_new_confirmation(): void
    {
        $proposal = $this->draft();
        $token = $this->token($proposal);
        $this->plan->update(['annual_price' => '150.00']);
        $this->from('/proposals/'.$proposal->id)->post('/proposals/'.$proposal->id.'/submit', ['quote_token' => $token])->assertSessionHasErrors('quote_token')->assertRedirect('/proposals/'.$proposal->id);
        $this->assertSame(ProposalStatus::Draft, $proposal->fresh()->status);
        $this->get('/proposals/'.$proposal->id)->assertOk()->assertSee('150,00');
        $this->submit($proposal);
        $this->assertSame('150.00', $proposal->fresh()->quoted_amount);
    }

    public function test_stale_composition_and_forged_confirmation_are_rejected(): void
    {
        $proposal = $this->draft();
        $token = $this->token($proposal);
        $this->service->update(['description' => 'Alterado']);
        foreach ([$token, 'forged-token'] as $invalid) {
            $this->post('/proposals/'.$proposal->id.'/submit', ['quote_token' => $invalid])->assertSessionHasErrors('quote_token');
        }
        $this->assertNull($proposal->fresh()->services_snapshot);
        $this->assertSame(ProposalStatus::Draft, $proposal->fresh()->status);
    }

    public function test_unavailable_plan_blocks_draft_and_submission(): void
    {
        $proposal = $this->draft();
        $token = $this->token($proposal);
        $this->service->update(['is_active' => false]);
        $this->post('/proposals', ['vehicle_id' => $this->vehicle->id, 'plan_id' => $this->plan->id])->assertSessionHasErrors('plan_id');
        $this->post('/proposals/'.$proposal->id.'/submit', ['quote_token' => $token])->assertSessionHasErrors('plan_id');
        $this->assertSame(ProposalStatus::Draft, $proposal->fresh()->status);
        $this->assertNull($proposal->fresh()->submitted_at);
    }

    public function test_proposal_links_protect_vehicle_plan_and_historical_service(): void
    {
        $proposal = $this->draft();
        $this->delete('/vehicles/'.$this->vehicle->id)->assertSessionHasErrors('vehicle');
        $attributes = $this->vehicle->only(['license_plate', 'brand', 'model', 'manufacture_year']);
        $this->put('/vehicles/'.$this->vehicle->id, [...$attributes, 'brand' => 'Alteração em rascunho'])->assertSessionHasNoErrors();
        $this->submit($proposal);
        $this->put('/vehicles/'.$this->vehicle->id, [...$attributes, 'brand' => 'Outra marca'])->assertSessionHasErrors('vehicle');
        $this->actingAs(User::factory()->create(['role' => UserRole::Administrator]));
        $this->delete('/plans/'.$this->plan->id)->assertSessionHasErrors('plan');
        $replacement = Service::create(['name' => 'Chaveiro', 'description' => 'Novo', 'is_active' => true]);
        $this->put('/plans/'.$this->plan->id, ['name' => 'Teste', 'description' => 'Plano', 'annual_price' => '999.00', 'is_active' => 1, 'services' => [$replacement->id]])->assertSessionHasNoErrors();
        $this->delete('/services/'.$this->service->id)->assertSessionHasErrors('service');
        $this->assertDatabaseHas('services', ['id' => $this->service->id]);
        $this->assertSame('123.45', $proposal->fresh()->quoted_amount);
        $this->assertSame($this->service->id, $proposal->fresh()->services_snapshot[0]['id']);
    }

    public function test_failure_during_submit_rolls_back_status_and_snapshot(): void
    {
        $proposal = $this->draft();
        $token = $this->token($proposal);
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'update "proposals"')) {
                throw new \RuntimeException('Falha simulada');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->post('/proposals/'.$proposal->id.'/submit', ['quote_token' => $token]);
            $this->fail('A falha deveria interromper o envio.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falha simulada', $exception->getMessage());
        }
        $this->assertSame(ProposalStatus::Draft, $proposal->fresh()->status);
        $this->assertNull($proposal->fresh()->services_snapshot);
        $this->assertNull($proposal->fresh()->submitted_at);
    }

    public function test_seed_preserves_existing_proposals(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->assertDatabaseCount('proposals', 2);
        $submitted = Proposal::where('status', 'submitted')->firstOrFail();
        $this->assertNotEmpty($submitted->services_snapshot);
        $this->assertNotNull($submitted->submitted_at);
        $draft = Proposal::where('status', 'draft')->firstOrFail();
        $draft->update(['quoted_amount' => '432.10']);
        $this->seed(ProposalSeeder::class);
        $this->assertDatabaseCount('proposals', 2);
        $this->assertSame('432.10', $draft->fresh()->quoted_amount);
        foreach (Proposal::with('vehicle')->get() as $row) {
            $this->assertSame($row->user_id, $row->vehicle->user_id);
        }
    }
}
