<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->unique()->constrained()->restrictOnDelete();
            $table->string('contract_number', 50)->unique();
            $table->timestamp('contracted_at');
            $table->date('starts_on');
            $table->date('ends_on')->index();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE insurance_contracts ADD CONSTRAINT insurance_contracts_period_check CHECK (ends_on > starts_on)');
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_contracts');
    }
};
