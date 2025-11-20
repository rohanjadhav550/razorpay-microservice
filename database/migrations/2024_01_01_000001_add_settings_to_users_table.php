<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('razorpay_key_id')->nullable()->after('remember_token');
            $table->text('razorpay_key_secret')->nullable()->after('razorpay_key_id');
            $table->text('anthropic_api_key')->nullable()->after('razorpay_key_secret');
            $table->text('openai_api_key')->nullable()->after('anthropic_api_key');
            $table->string('ai_provider')->default('anthropic')->after('openai_api_key');
            $table->json('enabled_services')->nullable()->after('ai_provider');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'razorpay_key_id',
                'razorpay_key_secret',
                'anthropic_api_key',
                'openai_api_key',
                'ai_provider',
                'enabled_services',
            ]);
        });
    }
};
