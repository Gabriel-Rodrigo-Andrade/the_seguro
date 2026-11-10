<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VehicleCrudTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $changes = []): array
    {
        return array_replace(['license_plate' => 'XYZ1A23', 'brand' => 'Exemplo', 'model' => 'Modelo', 'manufacture_year' => 2020, 'model_year' => null, 'color' => null], $changes);
    }

    public function test_customer_crud_normalizes_plate_ignores_owner_and_never_calls_api(): void
    {
        Http::fake();
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($customer)->get('/vehicles/create')->assertOk()->assertSee('Simulação');
        $this->post('/vehicles', $this->data(['license_plate' => 'xyz-1a23', 'user_id' => $other->id]))->assertSessionHasNoErrors();
        $vehicle = Vehicle::firstOrFail();
        $this->assertSame($customer->id, $vehicle->user_id);
        $this->assertSame('XYZ1A23', $vehicle->license_plate);
        $this->get('/vehicles?search=xyz-1a23')->assertOk()->assertSee('XYZ1A23');
        $this->get('/vehicles/'.$vehicle->id)->assertOk();
        $this->get('/vehicles/'.$vehicle->id.'/edit')->assertOk()->assertDontSee('Consultar placa');
        $this->put('/vehicles/'.$vehicle->id, $this->data(['color' => 'Azul']))->assertSessionHasNoErrors();
        $this->assertSame('Azul', $vehicle->fresh()->color);
        $this->delete('/vehicles/'.$vehicle->id)->assertRedirect('/vehicles');
        $this->assertDatabaseCount('vehicles', 0);
        Http::assertNothingSent();
    }

    public function test_customer_cannot_access_another_vehicle_or_change_ownership(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $vehicle = $owner->vehicles()->create($this->data());
        $this->actingAs($other)->get('/vehicles?search=Exemplo')->assertDontSee('XYZ1A23');
        $this->get('/vehicles/'.$vehicle->id)->assertForbidden();
        $this->get('/vehicles/'.$vehicle->id.'/edit')->assertForbidden();
        $this->put('/vehicles/'.$vehicle->id, $this->data())->assertForbidden();
        $this->delete('/vehicles/'.$vehicle->id)->assertForbidden();
        $this->actingAs($owner)->put('/vehicles/'.$vehicle->id, $this->data(['user_id' => $other->id]))->assertSessionHasErrors('user_id');
        $this->assertSame($owner->id, $vehicle->fresh()->user_id);
    }

    public function test_broker_has_empty_list_and_cannot_create_or_access_vehicle(): void
    {
        $owner = User::factory()->create();
        $vehicle = $owner->vehicles()->create($this->data());
        $this->actingAs(User::factory()->create(['role' => 'corretor']))->get('/vehicles')->assertOk()->assertDontSee('XYZ1A23')->assertDontSee('Cadastrar veículo');
        $this->get('/vehicles/create')->assertForbidden();
        $this->post('/vehicles', $this->data())->assertForbidden();
        $this->get('/vehicles/'.$vehicle->id)->assertForbidden();
    }

    public function test_administrator_requires_active_customer_and_cannot_change_owner_on_update(): void
    {
        $admin = User::factory()->create(['role' => 'administrador']);
        $owner = User::factory()->create();
        $inactive = User::factory()->create(['is_active' => false]);
        $this->actingAs($admin)->get('/vehicles/create')->assertSee($owner->email)->assertDontSee($inactive->email);
        foreach ([$admin->id, $inactive->id, -1] as $id) {
            $this->post('/vehicles', $this->data(['user_id' => $id]))->assertSessionHasErrors('user_id');
        }
        $this->post('/vehicles', $this->data(['user_id' => $owner->id]))->assertSessionHasNoErrors();
        $vehicle = Vehicle::firstOrFail();
        $this->get('/vehicles')->assertSee('XYZ1A23');
        $this->get('/vehicles/'.$vehicle->id)->assertOk();
        $this->put('/vehicles/'.$vehicle->id, $this->data(['user_id' => $inactive->id]))->assertSessionHasErrors('user_id');
    }

    public function test_validation_rejects_duplicate_invalid_plate_missing_brand_and_future_year(): void
    {
        $owner = User::factory()->create();
        $owner->vehicles()->create($this->data());
        $this->actingAs($owner)->post('/vehicles', $this->data())->assertSessionHasErrors('license_plate');
        $this->post('/vehicles', $this->data(['license_plate' => 'wrong', 'brand' => '', 'manufacture_year' => now()->year + 2]))->assertSessionHasErrors(['license_plate', 'brand', 'manufacture_year']);
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_customer_with_vehicle_cannot_change_role_but_can_be_deactivated(): void
    {
        $owner = User::factory()->create();
        $owner->vehicles()->create($this->data());
        $this->actingAs(User::factory()->create(['role' => 'administrador']))->put('/admin/users/'.$owner->id, ['name' => $owner->name, 'email' => $owner->email, 'role' => 'corretor', 'is_active' => true])->assertSessionHasErrors('role');
        $this->assertSame('cliente', $owner->fresh()->role->value);
        $this->patch('/admin/users/'.$owner->id.'/deactivate')->assertSessionHasNoErrors();
        $this->assertFalse($owner->fresh()->is_active);
    }

    public function test_guest_cannot_access_vehicle_routes(): void
    {
        $this->get('/vehicles')->assertRedirect('/login');
        $this->post('/vehicles', $this->data())->assertRedirect('/login');
    }
}
