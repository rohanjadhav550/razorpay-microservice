<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table) {
            $table->foreignId('database_connection_id')
                ->nullable()
                ->after('workflow_id')
                ->constrained('database_connections')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('agent_conversations', function (Blueprint $table) {
            $table->dropForeign(['database_connection_id']);
            $table->dropColumn('database_connection_id');
        });
    }
};
