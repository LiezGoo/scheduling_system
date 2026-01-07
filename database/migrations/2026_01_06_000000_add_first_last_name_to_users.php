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
        Schema::table('users', function (Blueprint $table) {
            // Add new columns after email
            $table->string('first_name')->nullable()->after('email');
            $table->string('last_name')->nullable()->after('first_name');
        });

        // Migrate existing data from 'name' to 'first_name' and 'last_name'
        DB::statement('
            UPDATE users
            SET first_name = TRIM(SUBSTRING_INDEX(name, " ", 1)),
                last_name = TRIM(IF(
                    CHAR_LENGTH(name) - CHAR_LENGTH(REPLACE(name, " ", "")) = 0,
                    "",
                    SUBSTRING(name, CHAR_LENGTH(SUBSTRING_INDEX(name, " ", 1)) + 2)
                ))
            WHERE name IS NOT NULL
        ');

        // Make the new columns non-nullable after migration
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }
};
