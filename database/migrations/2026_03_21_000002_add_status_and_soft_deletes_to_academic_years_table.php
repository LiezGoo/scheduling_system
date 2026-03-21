<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_years', 'status')) {
                $table->string('status', 20)->default('inactive')->after('is_active');
            }

            if (!Schema::hasColumn('academic_years', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Keep status synchronized with the existing active flag for existing records.
        DB::table('academic_years')
            ->where('is_active', true)
            ->update(['status' => 'active']);

        DB::table('academic_years')
            ->where('is_active', false)
            ->update(['status' => 'inactive']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            if (Schema::hasColumn('academic_years', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('academic_years', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
