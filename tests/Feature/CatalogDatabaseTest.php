<?php

namespace Tests\Feature;

use Database\Seeders\PlanSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalog(): void
    {
        $this->seed([ServiceSeeder::class, PlanSeeder::class]);
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

    public function test_seed_creates_shared_services_and_preserves_changes(): void
    {
        $this->seedCatalog();
        $this->assertDatabaseCount('services', 2);
        $this->assertDatabaseCount('plans', 2);
        $this->assertDatabaseCount('plan_service', 3);
        $guincho = DB::table('services')->where('name', 'Guincho')->value('id');
        $this->assertSame(2, DB::table('plan_service')->where('service_id', $guincho)->count());
        DB::table('plans')->where('name', 'Completo')->update(['annual_price' => '1111.11', 'is_active' => false]);
        DB::table('services')->where('id', $guincho)->update(['description' => 'Descrição revisada', 'is_active' => false]);
        $complete = DB::table('plans')->where('name', 'Completo')->value('id');
        DB::table('plan_service')->where('plan_id', $complete)->where('service_id', $guincho)->delete();
        $this->seedCatalog();
        $this->assertDatabaseCount('services', 2);
        $this->assertDatabaseCount('plans', 2);
        $this->assertDatabaseCount('plan_service', 2);
        $this->assertDatabaseHas('plans', ['name' => 'Completo', 'annual_price' => '1111.11', 'is_active' => false]);
        $this->assertDatabaseHas('services', ['id' => $guincho, 'description' => 'Descrição revisada', 'is_active' => false]);
    }

    public function test_duplicate_link_and_names_are_rejected(): void
    {
        $this->seedCatalog();
        $link = DB::table('plan_service')->first();
        $this->assertConstraint('23505', fn () => DB::table('plan_service')->insert(['plan_id' => $link->plan_id, 'service_id' => $link->service_id]));
        $this->assertConstraint('23505', fn () => DB::table('services')->insert(['name' => 'Guincho', 'description' => 'Duplicado']));
        $this->assertConstraint('23505', fn () => DB::table('plans')->insert(['name' => 'Essencial', 'description' => 'Duplicado', 'annual_price' => '1.00']));
        $this->assertDatabaseCount('plan_service', 3);
    }

    public function test_zero_and_negative_prices_are_rejected(): void
    {
        foreach (['0.00', '-0.01'] as $price) {
            $this->assertConstraint('23514', fn () => DB::table('plans')->insert(['name' => 'Inválido', 'description' => 'Teste', 'annual_price' => $price]));
        }
        $this->assertDatabaseCount('plans', 0);
    }

    public function test_foreign_keys_and_restrict_deletion_are_enforced(): void
    {
        $this->seedCatalog();
        $link = DB::table('plan_service')->first();
        $this->assertConstraint('23503', fn () => DB::table('plan_service')->insert(['plan_id' => -1, 'service_id' => $link->service_id]));
        $this->assertConstraint('23503', fn () => DB::table('plan_service')->insert(['plan_id' => $link->plan_id, 'service_id' => -1]));
        $this->assertConstraint('23503', fn () => DB::table('plans')->where('id', $link->plan_id)->delete());
        $this->assertConstraint('23503', fn () => DB::table('services')->where('id', $link->service_id)->delete());
        $this->assertDatabaseCount('plan_service', 3);
    }

    public function test_missing_service_rolls_back_plans_and_links(): void
    {
        DB::table('services')->insert(['name' => 'Guincho', 'description' => 'Teste']);
        try {
            $this->seed(PlanSeeder::class);
            $this->fail('Serviço ausente deveria impedir a carga.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('A carga exige todos os serviços demonstrativos ativos.', $exception->getMessage());
        }
        $this->assertDatabaseCount('plans', 0);
        $this->assertDatabaseCount('plan_service', 0);
    }
}
