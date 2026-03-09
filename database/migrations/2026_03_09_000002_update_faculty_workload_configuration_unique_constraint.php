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
        Schema::table('faculty_workload_configurations', function (Blueprint $table) {
            // Remove hard uniqueness so soft-deleted rows do not block re-creation.
            $table->dropUnique('faculty_workload_configurations_user_id_program_id_unique');

            // Keep lookup performance for active duplicate checks.
            $table->index(['user_id', 'program_id'], 'fwc_user_program_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('faculty_workload_configurations', function (Blueprint $table) {
            $table->dropIndex('fwc_user_program_index');
            $table->unique(['user_id', 'program_id'], 'faculty_workload_configurations_user_id_program_id_unique');
        });
    }
};
