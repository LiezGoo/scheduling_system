<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_configurations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnDelete();

            $table->foreignId('academic_year_id')
                ->constrained('academic_years');

            $table->string('semester');
            $table->integer('year_level');
            $table->integer('number_of_blocks');

            $table->foreignId('department_head_id')
                ->constrained('users');

            $table->timestamps();

            $table->index(['department_head_id', 'created_at']);
            $table->index(['program_id', 'academic_year_id', 'semester', 'year_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_configurations');
    }
};
