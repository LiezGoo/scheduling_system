<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration extends the faculty_subjects table to support:
     * - Lecture hours
     * - Laboratory hours
     * - Computed teaching units (based on hour-to-unit conversion rules)
     *
     * CONVERSION RULES:
     * - Lecture: 1 hour = 1 unit
     * - Laboratory: 3 hours = 1 unit
     * - Total Units = Lecture Units + Lab Units
     */
    public function up(): void
    {
        Schema::table('faculty_subjects', function (Blueprint $table) {
            // Add lecture and lab hours columns
            $table->integer('lecture_hours')
                  ->nullable()
                  ->after('subject_id')
                  ->comment('Number of lecture hours per week for this subject assignment');

            $table->integer('lab_hours')
                  ->nullable()
                  ->after('lecture_hours')
                  ->comment('Number of laboratory hours per week for this subject assignment (must be divisible by 3)');

            // Computed units column (can be calculated dynamically, but stored for performance)
            $table->decimal('computed_units', 5, 2)
                  ->default(0)
                  ->after('lab_hours')
                  ->comment('Auto-calculated teaching units: (lecture_hours × 1) + (lab_hours ÷ 3)');

            // Remove max_sections as it's not needed per requirements
            $table->dropColumn('max_sections');

            // Keep max_load_units but make it nullable and update comment
            $table->integer('max_load_units')
                  ->nullable()
                  ->comment('Optional maximum total teaching load units for this instructor (across all subjects)')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faculty_subjects', function (Blueprint $table) {
            // Restore original structure
            $table->dropColumn(['lecture_hours', 'lab_hours', 'computed_units']);

            $table->integer('max_sections')
                  ->default(3)
                  ->after('subject_id')
                  ->comment('Maximum number of sections this instructor can teach for this subject');
        });
    }
};
