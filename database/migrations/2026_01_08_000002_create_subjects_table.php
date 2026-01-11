<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')->constrained()->cascadeOnDelete();
            $table->string('subject_code');
            $table->string('subject_name');
            $table->decimal('units', 3, 1); // e.g., 3.0, 2.5
            $table->decimal('lecture_hours', 3, 1)->default(0);
            $table->decimal('lab_hours', 3, 1)->default(0);
            $table->integer('year_level'); // 1, 2, 3, 4
            $table->integer('semester'); // 1 or 2
            $table->timestamps();

            // Ensure subject code is unique within a curriculum
            $table->unique(['curriculum_id', 'subject_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
