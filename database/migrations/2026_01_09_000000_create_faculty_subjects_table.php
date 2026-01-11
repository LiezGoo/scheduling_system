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
        Schema::create('faculty_subjects', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade')
                  ->comment('References the eligible instructor (instructor, program_head, or department_head)');

            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->onDelete('cascade')
                  ->comment('References the subject this faculty can teach');

            // Faculty Load Constraints
            $table->integer('max_sections')
                  ->default(3)
                  ->comment('Maximum number of sections this instructor can teach for this subject');

            $table->integer('max_load_units')
                  ->nullable()
                  ->comment('Optional override for maximum load units for this subject assignment');

            // Timestamps
            $table->timestamps();

            // Unique constraint: A user can only be assigned to a subject once
            $table->unique(['user_id', 'subject_id'], 'unique_faculty_subject');

            // Indexes for better query performance
            $table->index('user_id');
            $table->index('subject_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_subjects');
    }
};
