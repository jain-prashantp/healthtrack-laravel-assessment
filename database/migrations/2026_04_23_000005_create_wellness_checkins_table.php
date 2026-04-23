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
        Schema::create('wellness_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->timestampTz('checked_in_at');
            $table->unsignedTinyInteger('mood_score');
            $table->unsignedTinyInteger('energy_level')->nullable();
            $table->json('symptoms')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_holiday')->default(false);
            $table->string('holiday_name')->nullable();
            $table->json('weather_data')->nullable();
            $table->timestampTz('weather_fetched_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'checked_in_at']);
            $table->index(['patient_id', 'mood_score']);
            $table->index(['patient_id', 'is_holiday']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_checkins');
    }
};
