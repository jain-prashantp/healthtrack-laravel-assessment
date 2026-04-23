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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('patient');
            $table->string('country_code', 2)->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('assigned_doctor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->index(['role', 'is_active']);
            $table->index(['country_code', 'city']);
            $table->index(['assigned_doctor_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'is_active']);
            $table->dropIndex(['country_code', 'city']);
            $table->dropIndex(['assigned_doctor_id', 'is_active']);
            $table->dropConstrainedForeignId('assigned_doctor_id');
            $table->dropColumn([
                'role',
                'country_code',
                'city',
                'latitude',
                'longitude',
                'timezone',
                'is_active',
            ]);
        });
    }
};
