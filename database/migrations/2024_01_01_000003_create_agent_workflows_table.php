<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('database_connection_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('service_type'); // payment_link, order
            $table->json('custom_logic')->nullable();
            $table->string('status')->default('draft'); // draft, active, inactive
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_workflows');
    }
};
