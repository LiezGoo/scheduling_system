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
            // Add employment_type for faculty load constraints
            // NULL for non-teaching roles (student, etc.)
            // Place after program_id if faculty_scheme doesn't exist, otherwise after faculty_scheme
            if (Schema::hasColumn('users', 'faculty_scheme')) {
                $table->enum('employment_type', ['permanent', 'contract_27', 'contract_24'])
                      ->nullable()
                      ->after('faculty_scheme')
                      ->comment('Faculty employment type: permanent, contract_27 (27hrs max), contract_24 (24hrs max)');
            } else {
                $table->enum('employment_type', ['permanent', 'contract_27', 'contract_24'])
                      ->nullable()
                      ->after('program_id')
                      ->comment('Faculty employment type: permanent, contract_27 (27hrs max), contract_24 (24hrs max)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('employment_type');
        });
    }
};
