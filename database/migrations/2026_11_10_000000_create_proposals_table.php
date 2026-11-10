<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('vehicle_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->index()->constrained()->restrictOnDelete();
            $table->decimal('quoted_amount', 12, 2);
            $table->string('status', 20)->default('draft');
            $table->jsonb('services_snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
        DB::statement('ALTER TABLE proposals ADD CONSTRAINT proposals_amount_positive CHECK (quoted_amount > 0)');
        DB::statement("ALTER TABLE proposals ADD CONSTRAINT proposals_status_check CHECK (status IN ('draft', 'submitted', 'approved', 'rejected', 'contracted'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
