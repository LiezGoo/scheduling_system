<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by_program_head')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_department_head')->nullable()->constrained('users')->nullOnDelete();

            // Schedule Details
            $table->string('academic_year'); // e.g., "2025-2026"
            $table->string('semester'); // e.g., "1st Semester", "2nd Semester"
            $table->unsignedTinyInteger('year_level'); // 1, 2, 3, 4
            $table->string('block')->nullable(); // e.g., "A", "B", "C"

            // Status Workflow: DRAFT → PENDING → APPROVED
            $table->enum('status', ['DRAFT', 'PENDING', 'APPROVED', 'REJECTED'])->default('DRAFT');

            // Approval Timestamps
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('program_head_approved_at')->nullable();
            $table->timestamp('department_head_approved_at')->nullable();

            // Remarks
            $table->text('program_head_remarks')->nullable();
            $table->text('department_head_remarks')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['program_id', 'academic_year', 'semester', 'year_level']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
