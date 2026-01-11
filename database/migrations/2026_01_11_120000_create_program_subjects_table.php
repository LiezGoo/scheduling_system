<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->string('semester', 10);
            $table->timestamps();

            $table->unique(['program_id', 'subject_id', 'year_level', 'semester'], 'program_subject_unique');
            $table->index(['program_id', 'year_level', 'semester'], 'program_subject_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_subjects');
    }
};
