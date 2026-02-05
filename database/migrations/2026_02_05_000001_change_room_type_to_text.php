<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, migrate existing room_type_id values to text
        // Get all existing rooms with their room types
        $rooms = DB::table('rooms')
            ->join('room_types', 'rooms.room_type_id', '=', 'room_types.id')
            ->select('rooms.id', 'room_types.type_name')
            ->get();

        // Drop the foreign key constraint
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['room_type_id']);
        });

        // Change room_type_id to room_type with VARCHAR(50)
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('room_type', 50)->nullable()->after('building_id');
        });

        // Migrate the data
        foreach ($rooms as $room) {
            DB::table('rooms')
                ->where('id', $room->id)
                ->update(['room_type' => $room->type_name]);
        }

        // Make room_type NOT NULL after data migration
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('room_type', 50)->nullable(false)->change();
        });

        // Drop the old room_type_id column
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('room_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible because it changes the data structure
        // If you need to rollback, you'll need to manually recreate the room_types relationship
        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('room_type_id')->nullable()->after('building_id')->constrained()->restrictOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('room_type');
        });
    }
};
