<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_contract_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->index()->constrained()->restrictOnDelete();
            $table->text('description');
            $table->string('location');
            $table->string('status', 20)->default('open');
            $table->foreignId('assigned_to')->nullable()->index()->constrained('users')->restrictOnDelete();
            $table->text('response')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
        DB::statement("ALTER TABLE service_requests ADD CONSTRAINT service_requests_status_check CHECK (status IN ('open', 'in_progress', 'completed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('service_requests');
    }
};
