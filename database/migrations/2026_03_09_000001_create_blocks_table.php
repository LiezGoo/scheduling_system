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
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('semester_id')->constrained()->restrictOnDelete();
            $table->foreignId('year_level_id')->constrained()->restrictOnDelete();
            $table->string('block_name');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Unique constraint to prevent duplicate blocks
            $table->unique(
                ['program_id', 'academic_year_id', 'semester_id', 'year_level_id', 'block_name'],
                'blocks_unique_combination'
            );

            // Indexes for faster queries
            $table->index('program_id');
            $table->index('academic_year_id');
            $table->index('semester_id');
            $table->index('year_level_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
