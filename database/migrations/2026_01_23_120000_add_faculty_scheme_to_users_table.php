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
        Schema::table('users', function (Blueprint $table) {
            // Add faculty_scheme for work hour preferences
            // Options: "7:00-16:00", "8:00-17:00", "10:00-19:00"
            $table->string('faculty_scheme')
                  ->nullable()
                  ->after('program_id')
                  ->comment('Faculty work schedule preference: 7:00-16:00, 8:00-17:00, 10:00-19:00');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('faculty_scheme');
        });
    }
};
