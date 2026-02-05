<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_items', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('instructor_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('room_id')->constrained()->restrictOnDelete();

            // Time Details
            $table->string('day_of_week'); // Monday, Tuesday, etc.
            $table->time('start_time');
            $table->time('end_time');

            // Section (if needed)
            $table->string('section')->nullable();

            $table->timestamps();

            // Indexes for conflict detection (with custom names to avoid length limit)
            $table->index(['instructor_id', 'day_of_week', 'start_time', 'end_time'], 'schedule_items_instructor_idx');
            $table->index(['room_id', 'day_of_week', 'start_time', 'end_time'], 'schedule_items_room_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_items');
    }
};
