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
        Schema::table('rooms', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['building_id']);
            
            // Make building_id nullable and re-add foreign key
            $table->foreignId('building_id')->nullable()->change()->constrained()->nullOnDelete();
            
            // Add capacity and floor_level columns if they don't exist
            if (!Schema::hasColumn('rooms', 'capacity')) {
                $table->integer('capacity')->nullable();
            }
            if (!Schema::hasColumn('rooms', 'floor_level')) {
                $table->integer('floor_level')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Drop foreign key
            $table->dropForeign(['building_id']);
            
            // Make building_id required again
            $table->foreignId('building_id')->nullable(false)->change()->constrained()->restrictOnDelete();
            
            // Drop capacity and floor_level columns if they exist
            if (Schema::hasColumn('rooms', 'capacity')) {
                $table->dropColumn('capacity');
            }
            if (Schema::hasColumn('rooms', 'floor_level')) {
                $table->dropColumn('floor_level');
            }
        });
    }
};
