<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->index(['program_id', 'academic_year', 'semester', 'year_level', 'block'], 'schedules_preview_lookup_idx');
        });

        Schema::table('schedule_items', function (Blueprint $table) {
            $table->index(['schedule_id', 'day_of_week', 'start_time'], 'schedule_items_preview_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_items', function (Blueprint $table) {
            $table->dropIndex('schedule_items_preview_lookup_idx');
        });

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex('schedules_preview_lookup_idx');
        });
    }
};
