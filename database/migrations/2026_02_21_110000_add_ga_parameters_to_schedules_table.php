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
        Schema::table('schedules', function (Blueprint $table) {
            // Add ga_parameters JSON column to store genetic algorithm configuration
            $table->json('ga_parameters')->nullable()->after('status');
            
            // Add fitness_score to track schedule quality
            $table->decimal('fitness_score', 10, 2)->nullable()->after('ga_parameters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['ga_parameters', 'fitness_score']);
        });
    }
};
