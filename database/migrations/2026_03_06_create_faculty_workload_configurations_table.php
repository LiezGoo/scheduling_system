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
        Schema::create('faculty_workload_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Faculty member
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade'); // Program reference
            $table->string('contract_type'); // Full-Time, Part-Time, Contractual
            $table->integer('max_lecture_hours'); // Maximum lecture hours per week
            $table->integer('max_lab_hours')->default(0); // Maximum lab hours per week
            $table->integer('max_hours_per_day'); // Maximum teaching hours per day
            $table->json('available_days'); // ["Monday", "Tuesday", ...]
            $table->time('start_time')->nullable(); // Teaching window start time
            $table->time('end_time')->nullable(); // Teaching window end time
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users')->onDelete('set null')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indices for performance
            $table->index('program_id');
            $table->index('user_id');
            $table->index('contract_type');
            $table->index('is_active');

            // Unique constraint to prevent duplicate configurations for same faculty
            $table->unique(['user_id', 'program_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faculty_workload_configurations');
    }
};
