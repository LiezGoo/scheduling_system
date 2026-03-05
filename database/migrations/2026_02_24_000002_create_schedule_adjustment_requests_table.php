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
        Schema::create('schedule_adjustment_requests', function (Blueprint $table) {
            $table->id();
            
            // Schedule being adjusted
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            
            // Schedule item being adjusted (if applicable)
            $table->foreignId('schedule_item_id')->nullable()->constrained()->cascadeOnDelete();
            
            // User requesting the adjustment (Program Head or Instructor)
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            
            // Reason for adjustment request
            $table->text('reason');
            
            // Status: pending, approved, rejected
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            
            // Department Head who reviewed the request
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Response/remarks from reviewer
            $table->text('review_remarks')->nullable();
            
            // When the request was reviewed
            $table->timestamp('reviewed_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for common queries
            $table->index(['schedule_id', 'status']);
            $table->index(['requested_by', 'status']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_adjustment_requests');
    }
};
