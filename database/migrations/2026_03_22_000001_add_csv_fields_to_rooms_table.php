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
            if (!Schema::hasColumn('rooms', 'building')) {
                $table->string('building')->nullable()->after('room_name');
            }

            if (!Schema::hasColumn('rooms', 'floor')) {
                $table->unsignedInteger('floor')->nullable()->after('building');
            }

            if (!Schema::hasColumn('rooms', 'capacity')) {
                $table->unsignedInteger('capacity')->nullable()->after('floor');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('rooms', 'building')) {
                $columnsToDrop[] = 'building';
            }

            if (Schema::hasColumn('rooms', 'floor')) {
                $columnsToDrop[] = 'floor';
            }

            if (Schema::hasColumn('rooms', 'capacity')) {
                $columnsToDrop[] = 'capacity';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
