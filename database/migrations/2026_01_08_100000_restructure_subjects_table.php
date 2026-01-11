<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing subjects table first (it has FK to curricula)
        Schema::dropIfExists('subjects');

        // Now drop curricula table
        Schema::dropIfExists('curricula');

        // Recreate subjects table with new structure
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code')->unique(); // Globally unique
            $table->string('subject_name');
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->decimal('units', 3, 1); // e.g., 3.0, 2.5
            $table->decimal('lecture_hours', 3, 1)->default(0);
            $table->decimal('lab_hours', 3, 1)->default(0);
            $table->integer('year_level'); // 1, 2, 3, 4
            $table->integer('semester'); // 1 or 2
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');

        // Recreate the old structure if needed
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->string('curriculum_code')->unique();
            $table->string('curriculum_name');
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('academic_year');
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->string('subject_code');
            $table->string('subject_name');
            $table->decimal('units', 3, 1);
            $table->decimal('lecture_hours', 3, 1)->default(0);
            $table->decimal('lab_hours', 3, 1)->default(0);
            $table->integer('year_level');
            $table->integer('semester');
            $table->timestamps();

            $table->unique(['curriculum_id', 'subject_code']);
        });
    }
};
