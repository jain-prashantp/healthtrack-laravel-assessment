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
        Schema::create('wellness_weekly_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->date('week_start_date');
            $table->decimal('avg_mood_score', 5, 2)->nullable();
            $table->unsignedInteger('total_checkins')->default(0);
            $table->unsignedInteger('checkin_streak_days')->default(0);
            $table->json('most_common_symptoms')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'week_start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_weekly_stats');
    }
};
