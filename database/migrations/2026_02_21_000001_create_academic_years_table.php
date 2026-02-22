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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 20); // e.g., "2025-2026"
            $table->integer('start_year'); // e.g., 2025
            $table->integer('end_year'); // e.g., 2026
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // Unique constraint: Only one combination of start_year and end_year
            $table->unique(['start_year', 'end_year']);

            // Index for active status for faster queries
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
