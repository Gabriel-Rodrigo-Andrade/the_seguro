<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    private function service(string $name = 'Guincho'): Service
    {
        return Service::create(['name' => $name, 'description' => 'Assistência fictícia', 'is_active' => true]);
    }

    private function attributes(array $services): array
    {
        return ['name' => 'Plano teste', 'description' => 'Plano fictício', 'annual_price' => '120.50', 'is_active' => true, 'services' => $services];
    }

    public function test_admin_manages_services_and_plan_composition(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Administrator]));
        $this->get('/services/create')->assertOk();
        $this->post('/services', ['name' => 'Guincho', 'description' => 'Teste', 'is_active' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $first = Service::firstOrFail();
        $second = $this->service('Chaveiro');
        $this->get('/plans/create')->assertOk()->assertSee('Chaveiro');
        $this->post('/plans', $this->attributes([$first->id, $second->id]))->assertSessionHasNoErrors()->assertRedirect();
        $plan = Plan::firstOrFail();
        $this->assertSame('120.50', $plan->annual_price);
        $this->assertCount(2, $plan->services);
        $this->assertNotNull($plan->services->first()->pivot->created_at);
        $this->get('/plans/'.$plan->id)->assertOk()->assertSee('Guincho')->assertSee('Chaveiro');
        $this->get('/plans/'.$plan->id.'/edit')->assertOk();
        $this->put('/plans/'.$plan->id, $this->attributes([$second->id]))->assertSessionHasNoErrors();
        $this->assertSame([$second->id], $plan->fresh()->services->modelKeys());
        $this->get('/services/'.$first->id.'/edit')->assertOk();
        $this->put('/services/'.$first->id, ['name' => 'Guincho', 'description' => 'Revisado', 'is_active' => 0])->assertSessionHasNoErrors();
        $this->get('/services/'.$first->id)->assertOk()->assertSee('Revisado');
        $this->delete('/services/'.$first->id)->assertRedirect('/services');
        $this->delete('/plans/'.$plan->id)->assertRedirect('/plans');
        $this->assertDatabaseCount('plans', 0);
        $this->assertDatabaseCount('plan_service', 0);
        $this->assertDatabaseHas('services', ['id' => $second->id]);
    }

    public function test_customer_only_sees_available_plans_and_broker_sees_catalog(): void
    {
        $service = $this->service();
        $plan = Plan::create(collect($this->attributes([]))->except('services')->all());
        $plan->services()->attach($service);
        $customer = User::factory()->create();
        $this->actingAs($customer)->get('/plans')->assertOk()->assertSee('Plano teste');
        $this->get('/plans/'.$plan->id)->assertOk();
        $service->update(['is_active' => false]);
        $this->get('/plans')->assertOk()->assertDontSee('Plano teste');
        $this->get('/plans/'.$plan->id)->assertForbidden();
        $this->get('/services')->assertForbidden();
        $this->get('/services/'.$service->id)->assertForbidden();
        $service->update(['is_active' => true]);
        $plan->update(['is_active' => false]);
        $this->get('/plans/'.$plan->id)->assertForbidden();
        $plan->update(['is_active' => true]);
        $plan->services()->detach();
        $this->get('/plans/'.$plan->id)->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => UserRole::Broker]));
        $this->get('/plans')->assertOk()->assertSee('Plano teste');
        $this->get('/plans/'.$plan->id)->assertOk();
        $this->get('/services')->assertOk();
        $this->get('/services/'.$service->id)->assertOk();
    }

    public function test_customers_and_brokers_cannot_mutate_catalog(): void
    {
        $service = $this->service();
        $plan = Plan::create(collect($this->attributes([]))->except('services')->all());
        foreach ([UserRole::Customer, UserRole::Broker] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            foreach (['plans' => $plan, 'services' => $service] as $resource => $record) {
                $this->get('/'.$resource.'/create')->assertForbidden();
                $this->get('/'.$resource.'/'.$record->id.'/edit')->assertForbidden();
                $this->post('/'.$resource, [])->assertForbidden();
                $this->put('/'.$resource.'/'.$record->id, [])->assertForbidden();
                $this->delete('/'.$resource.'/'.$record->id)->assertForbidden();
            }
        }
        $this->assertDatabaseCount('plans', 1);
        $this->assertDatabaseCount('services', 1);
    }

    public function test_invalid_prices_and_compositions_do_not_persist(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Administrator]));
        $service = $this->service();
        foreach (['0', '-1', '1.001', '10000000000.00', 'abc'] as $price) {
            $this->post('/plans', [...$this->attributes([$service->id]), 'annual_price' => $price])->assertSessionHasErrors('annual_price');
        }
        $this->post('/plans', $this->attributes([]))->assertSessionHasErrors('services');
        $this->post('/plans', $this->attributes([$service->id, $service->id]))->assertSessionHasErrors('services.0');
        $this->post('/plans', $this->attributes([-1]))->assertSessionHasErrors('services.0');
        $service->update(['is_active' => false]);
        $this->post('/plans', $this->attributes([$service->id]))->assertSessionHasErrors('services.0');
        $this->post('/services', ['name' => '', 'description' => '', 'is_active' => 1])->assertSessionHasErrors(['name', 'description']);
        $this->post('/services', ['name' => 'Guincho', 'description' => 'Duplicado', 'is_active' => 1])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('plans', 0);
        $this->assertDatabaseCount('plan_service', 0);
    }

    public function test_linked_service_cannot_be_deleted_but_can_be_deactivated(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Administrator]));
        $service = $this->service();
        $this->post('/plans', $this->attributes([$service->id]))->assertSessionHasNoErrors();
        $this->from('/services/'.$service->id)->delete('/services/'.$service->id)->assertSessionHasErrors('service');
        $this->put('/services/'.$service->id, ['name' => 'Guincho', 'description' => 'Teste', 'is_active' => false])->assertSessionHasNoErrors();
        $this->assertFalse($service->fresh()->is_active);
        $this->assertDatabaseCount('plan_service', 1);
    }

    public function test_pivot_failure_rolls_back_new_plan(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Administrator]));
        $service = $this->service();
        DB::listen(function ($query) {
            if (str_starts_with($query->sql, 'insert into "plan_service"')) {
                throw new \RuntimeException('Falha simulada no vínculo');
            }
        });
        $this->withoutExceptionHandling();
        try {
            $this->post('/plans', $this->attributes([$service->id]));
            $this->fail('A falha simulada deveria interromper o cadastro.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falha simulada no vínculo', $exception->getMessage());
        }
        $this->assertDatabaseCount('plans', 0);
        $this->assertDatabaseCount('plan_service', 0);
    }

    public function test_guest_and_inactive_accounts_cannot_access_catalog(): void
    {
        $this->get('/plans')->assertRedirect('/login');
        $this->get('/services')->assertRedirect('/login');
        $this->actingAs(User::factory()->create(['role' => UserRole::Administrator, 'is_active' => false]));
        $this->get('/plans')->assertRedirect('/login');
    }
}
