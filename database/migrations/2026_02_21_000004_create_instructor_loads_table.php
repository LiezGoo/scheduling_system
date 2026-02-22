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
        Schema::create('instructor_loads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('program_id')
                ->constrained('programs')
                ->onDelete('cascade');
            $table->foreignId('subject_id')
                ->constrained('subjects')
                ->onDelete('cascade');
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->onDelete('cascade');
            $table->string('semester');
            $table->unsignedTinyInteger('year_level');
            $table->string('block_section');
            $table->unsignedInteger('lec_hours');
            $table->unsignedInteger('lab_hours');
            $table->unsignedInteger('total_hours');
            $table->timestamps();

            $table->unique(
                [
                    'instructor_id',
                    'program_id',
                    'subject_id',
                    'academic_year_id',
                    'semester',
                    'year_level',
                    'block_section',
                ],
                'instructor_loads_unique_assignment'
            );

            $table->index(['instructor_id', 'academic_year_id', 'semester']);
            $table->index(['program_id', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_loads');
    }
};
