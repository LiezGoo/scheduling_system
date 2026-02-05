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
        if (Schema::hasColumn('rooms', 'building_id')) {
            Schema::table('rooms', function (Blueprint $table) {
                $table->dropForeign(['building_id']);
            });
        }

        Schema::table('rooms', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('rooms', 'building_id')) {
                $columnsToDrop[] = 'building_id';
            }
            if (Schema::hasColumn('rooms', 'capacity')) {
                $columnsToDrop[] = 'capacity';
            }
            if (Schema::hasColumn('rooms', 'floor_level')) {
                $columnsToDrop[] = 'floor_level';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'building_id')) {
                $table->foreignId('building_id')
                    ->nullable()
                    ->after('room_name')
                    ->constrained()
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('rooms', 'capacity')) {
                $table->integer('capacity')->nullable()->after('room_type');
            }

            if (!Schema::hasColumn('rooms', 'floor_level')) {
                $table->integer('floor_level')->nullable()->after('capacity');
            }
        });
    }
};
