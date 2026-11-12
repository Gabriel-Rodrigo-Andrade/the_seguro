<?php

namespace Tests\Feature;

use App\Actions\Assistance\UpdateServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\InsuranceContract;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\InsuranceContractSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\ProposalReviewSeeder;
use Database\Seeders\ProposalSeeder;
use Database\Seeders\ServiceRequestSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\VehicleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AssistanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private InsuranceContract $contract;

    private User $owner;

    private User $broker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([UserSeeder::class, VehicleSeeder::class, ServiceSeeder::class, PlanSeeder::class, ProposalSeeder::class, ProposalReviewSeeder::class, InsuranceContractSeeder::class]);
        $this->contract = InsuranceContract::orderBy('id')->firstOrFail();
        $this->owner = $this->contract->proposal->customer;
        $this->broker = User::where('role', 'corretor')->firstOrFail();
        $this->actingAs($this->owner);
    }

    private function attributes(): array
    {
        return ['insurance_contract_id' => $this->contract->id, 'service_id' => $this->contract->proposal->services_snapshot[0]['id'], 'description' => 'Veículo parado', 'location' => 'Rua fictícia'];
    }

    private function open(): ServiceRequest
    {
        $this->post('/assistance', $this->attributes())->assertSessionHasNoErrors()->assertRedirect();

        return ServiceRequest::latest('id')->firstOrFail();
    }

    public function test_open_uses_snapshot_and_ignores_privileged_fields(): void
    {
        $service = Service::findOrFail($this->attributes()['service_id']);
        $original = $service->name;
        $service->update(['name' => 'Nome novo', 'description' => 'Alterada', 'is_active' => false]);
        $this->get('/assistance/create')->assertOk()->assertSee($original)->assertDontSee('Nome novo');
        $this->post('/assistance', [...$this->attributes(), 'status' => 'completed', 'assigned_to' => $this->broker->id, 'response' => 'Forjada', 'completed_at' => now()])->assertSessionHasNoErrors();
        $request = ServiceRequest::firstOrFail();
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'status' => 'open', 'assigned_to' => null, 'response' => null, 'completed_at' => null]);
        $this->get('/assistance/'.$request->id)->assertOk()->assertSee($original)->assertDontSee('Nome novo');
        $this->get('/assistance')->assertOk()->assertSee($original);
    }

    public function test_foreign_contract_uncontracted_service_and_missing_fields_are_blocked(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other)->post('/assistance', $this->attributes())->assertSessionHasErrors('insurance_contract_id');
        $this->actingAs($this->owner)->post('/assistance', [...$this->attributes(), 'service_id' => -1])->assertSessionHasErrors('service_id');
        $this->post('/assistance', [...$this->attributes(), 'description' => '', 'location' => ''])->assertSessionHasErrors(['description', 'location']);
        $this->assertDatabaseCount('service_requests', 0);
    }

    public function test_end_is_exclusive_but_existing_request_can_be_completed_after_expiry(): void
    {
        $end = CarbonImmutable::parse($this->contract->ends_on->toDateString(), 'America/Sao_Paulo');
        $this->travelTo($end->subSecond());
        $request = $this->open();
        $this->travelTo($end);
        $this->post('/assistance', $this->attributes())->assertSessionHasErrors('insurance_contract_id');
        $this->actingAs($this->broker)->patch('/assistance/'.$request->id.'/status', ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->patch('/assistance/'.$request->id.'/status', ['status' => 'completed', 'response' => 'Concluído após vencimento'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'status' => 'completed']);
    }

    public function test_sequence_assignee_and_mandatory_response(): void
    {
        $request = $this->open();
        $url = '/assistance/'.$request->id.'/status';
        $this->patch($url, ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($this->broker)->patch($url, ['status' => 'completed', 'response' => 'Pular etapa'])->assertForbidden();
        $this->patch($url, ['status' => 'in_progress', 'assigned_to' => -1, 'description' => 'Mudado', 'location' => 'Outro'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'assigned_to' => $this->broker->id, 'description' => 'Veículo parado', 'location' => 'Rua fictícia']);
        $this->patch($url, ['status' => 'completed', 'response' => '   '])->assertSessionHasErrors('response');
        $this->actingAs(User::factory()->create(['role' => UserRole::Broker]))->patch($url, ['status' => 'completed', 'response' => 'Alheio'])->assertForbidden();
        $this->actingAs($this->broker)->patch($url, ['status' => 'completed', 'response' => 'Finalizado', 'completed_at' => '2000-01-01'])->assertSessionHasNoErrors();
        $this->assertTrue($request->fresh()->completed_at->isToday());
        $this->patch($url, ['status' => 'in_progress'])->assertForbidden();
        $this->actingAs($this->owner)->get('/assistance/'.$request->id)->assertOk()->assertSee('Finalizado');
    }

    public function test_admin_can_complete_another_operators_request_without_reassigning(): void
    {
        $request = $this->open();
        $this->actingAs($this->broker)->patch('/assistance/'.$request->id.'/status', ['status' => 'in_progress'])->assertSessionHasNoErrors();
        $this->actingAs(User::factory()->create(['role' => UserRole::Administrator]))->patch('/assistance/'.$request->id.'/status', ['status' => 'completed', 'response' => 'Concluído pelo administrador'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'status' => 'completed', 'assigned_to' => $this->broker->id]);
    }

    public function test_stale_take_cannot_overwrite_assignee(): void
    {
        $request = $this->open();
        $action = app(UpdateServiceRequestStatus::class);
        $action->handle($this->broker, $request, 'in_progress', null);
        try {
            $action->handle(User::factory()->create(['role' => UserRole::Broker]), $request, 'in_progress', null);
            $this->fail('Deveria rejeitar disputa.');
        } catch (AuthorizationException) {
            $this->assertSame($this->broker->id, $request->fresh()->assigned_to);
        }
    }

    public function test_visibility_inactive_and_absence_of_edit_delete(): void
    {
        $request = $this->open();
        $this->actingAs(User::factory()->create())->get('/assistance/'.$request->id)->assertForbidden();
        $this->get('/assistance')->assertOk()->assertDontSee('ABC1D23');
        $this->actingAs($this->broker)->get('/assistance')->assertOk()->assertSee('ABC1D23');
        $this->get('/assistance/create')->assertForbidden();
        $this->post('/assistance', $this->attributes())->assertForbidden();
        $this->delete('/assistance/'.$request->id)->assertStatus(405);
        $this->put('/assistance/'.$request->id, [])->assertStatus(405);
        $this->owner->forceFill(['is_active' => false])->save();
        $this->actingAs($this->owner)->post('/assistance', $this->attributes())->assertRedirect('/login');
        $this->get('/assistance')->assertRedirect('/login');
    }

    public function test_failed_completion_rolls_back_response_and_state(): void
    {
        $request = $this->open();
        app(UpdateServiceRequestStatus::class)->handle($this->broker, $request, 'in_progress', null);
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'update "service_requests"')) {
                throw new \RuntimeException('Falha simulada');
            }
        });
        try {
            app(UpdateServiceRequestStatus::class)->handle($this->broker, $request, 'completed', 'Resposta');
            $this->fail('Deveria falhar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falha simulada', $exception->getMessage());
        }
        $this->assertDatabaseHas('service_requests', ['id' => $request->id, 'status' => 'in_progress', 'response' => null, 'completed_at' => null]);
    }

    public function test_seed_history_is_coherent_and_reexecution_preserves_changes(): void
    {
        $this->seed(ServiceRequestSeeder::class);
        $this->assertDatabaseCount('service_requests', 3);
        $this->assertSame(['completed', 'in_progress', 'open'], ServiceRequest::orderBy('status')->pluck('status')->map(fn ($status) => $status->value)->all());
        foreach (ServiceRequest::with('contract.proposal')->get() as $request) {
            $this->assertTrue($request->contract->isActiveAt($request->created_at));
            $this->assertNotEmpty($request->contractedService());
        }
        $open = ServiceRequest::where('status', 'open')->firstOrFail();
        app(UpdateServiceRequestStatus::class)->handle($this->broker, $open, 'in_progress', null);
        $this->seed(ServiceRequestSeeder::class);
        $this->assertDatabaseCount('service_requests', 3);
        $this->assertSame('in_progress', $open->fresh()->status->value);
    }
}
