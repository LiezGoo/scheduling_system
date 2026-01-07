<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * IMPORTANT: Only run this migration AFTER you have confirmed that:
     * 1. All existing data has been successfully migrated to first_name and last_name
     * 2. All code has been updated to use the new fields
     * 3. The system has been tested thoroughly
     * 4. No references to the 'name' column remain in the codebase
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('id');
        });
    }
};
