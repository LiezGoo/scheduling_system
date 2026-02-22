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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->onDelete('cascade'); // Delete semesters when academic year is deleted
            $table->string('name', 50); // e.g., "1st Semester", "2nd Semester", "Summer"
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            // Unique constraint: One semester name per academic year
            $table->unique(['academic_year_id', 'name']);

            // Index for active status and lookups
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
