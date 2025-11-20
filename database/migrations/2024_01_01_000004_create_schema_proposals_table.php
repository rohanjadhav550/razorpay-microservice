<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schema_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workflow_id')->nullable()->constrained('agent_workflows')->onDelete('cascade');
            $table->foreignId('database_connection_id')->constrained()->onDelete('cascade');
            $table->text('description');
            $table->text('er_diagram'); // Mermaid syntax
            $table->json('proposed_changes'); // Tables, columns, relationships
            $table->json('migrations')->nullable(); // Generated migration code
            $table->string('status')->default('pending'); // pending, approved, rejected, applied
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schema_proposals');
    }
};
