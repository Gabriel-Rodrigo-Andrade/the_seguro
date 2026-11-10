<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Database\Seeders\VehicleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VehicleDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function attributes(int $owner): array
    {
        return ['user_id' => $owner, 'license_plate' => 'XYZ1A23', 'brand' => 'Exemplo', 'model' => 'Teste', 'manufacture_year' => 2020, 'created_at' => now(), 'updated_at' => now()];
    }

    private function assertConstraint(string $state, callable $operation): void
    {
        try {
            DB::transaction($operation);
            $this->fail('O PostgreSQL deveria rejeitar a operação.');
        } catch (QueryException $exception) {
            $this->assertSame($state, $exception->errorInfo[0]);
        }
    }

    public function test_duplicate_plate_is_rejected(): void
    {
        $owner = User::factory()->create();
        DB::table('vehicles')->insert($this->attributes($owner->id));
        $this->assertConstraint('23505', fn () => DB::table('vehicles')->insert($this->attributes($owner->id)));
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_nonexistent_owner_is_rejected(): void
    {
        $this->assertFalse(DB::table('users')->where('id', -1)->exists());
        $this->assertConstraint('23503', fn () => DB::table('vehicles')->insert($this->attributes(-1)));
        $this->assertDatabaseCount('vehicles', 0);
    }

    public function test_owner_with_vehicle_cannot_be_deleted(): void
    {
        $owner = User::factory()->create();
        DB::table('vehicles')->insert($this->attributes($owner->id));
        $this->assertConstraint('23503', fn () => DB::table('users')->where('id', $owner->id)->delete());
        $this->assertDatabaseHas('users', ['id' => $owner->id]);
    }

    public function test_seed_links_two_customers_and_preserves_changes_on_reexecution(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(VehicleSeeder::class);
        $this->assertDatabaseCount('vehicles', 2);
        $rows = DB::table('vehicles')->join('users', 'users.id', '=', 'vehicles.user_id')->orderBy('license_plate')->get(['users.email', 'vehicles.color', 'vehicles.model_year']);
        $this->assertSame('cliente1@theseguro.test', $rows[0]->email);
        $this->assertSame('cliente2@theseguro.test', $rows[1]->email);
        $this->assertSame(2021, $rows[0]->model_year);
        $this->assertNull($rows[1]->model_year);
        $this->assertNull($rows[1]->color);
        DB::table('vehicles')->where('license_plate', 'ABC1D23')->update(['color' => 'Azul']);
        $this->seed(VehicleSeeder::class);
        $this->assertDatabaseCount('vehicles', 2);
        $this->assertDatabaseHas('vehicles', ['license_plate' => 'ABC1D23', 'color' => 'Azul']);
    }

    public function test_missing_second_customer_rolls_back_entire_seed(): void
    {
        User::factory()->create(['email' => 'cliente1@theseguro.test']);
        try {
            $this->seed(VehicleSeeder::class);
            $this->fail('A ausência de proprietário deve impedir a carga.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('A carga exige os dois clientes demonstrativos ativos.', $exception->getMessage());
        }
        $this->assertDatabaseCount('vehicles', 0);
    }
}
