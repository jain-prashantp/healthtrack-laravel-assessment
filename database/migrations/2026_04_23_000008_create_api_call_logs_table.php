<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_call_logs', function (Blueprint $table) {
            $table->id();
            $table->string('api_name');
            $table->string('endpoint');
            $table->string('method', 10)->default('GET');
            $table->json('request_params')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->boolean('was_cached')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['api_name', 'created_at']);
            $table->index(['response_status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_call_logs');
    }
};
