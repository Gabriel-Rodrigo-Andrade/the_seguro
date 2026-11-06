<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->demonstrationUsers() as $user) {
                $this->createIfMissing($user);
            }
        });
    }

    private function demonstrationUsers(): array
    {
        return [
            ['name' => 'Cliente Demonstração 1', 'email' => 'cliente1@theseguro.test', 'role' => 'cliente'],
            ['name' => 'Cliente Demonstração 2', 'email' => 'cliente2@theseguro.test', 'role' => 'cliente'],
            ['name' => 'Corretor Demonstração', 'email' => 'corretor@theseguro.test', 'role' => 'corretor'],
            ['name' => 'Administrador Demonstração', 'email' => 'admin@theseguro.test', 'role' => 'administrador'],
        ];
    }

    private function createIfMissing(array $user): void
    {
        $user['email'] = strtolower($user['email']);

        if (DB::table('users')->where('email', $user['email'])->exists()) {
            return;
        }

        DB::table('users')->insert([
            ...$user,
            'password' => Hash::make('TheSeguro@123'),
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
