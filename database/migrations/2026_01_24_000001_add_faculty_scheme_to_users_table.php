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
            // Nullable: students and non-teaching roles should not have a scheme
            if (!Schema::hasColumn('users', 'faculty_scheme')) {
                $table->string('faculty_scheme')->nullable()->after('program_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'faculty_scheme')) {
                $table->dropColumn('faculty_scheme');
            }
        });
    }
};
