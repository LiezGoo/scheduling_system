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
        Schema::table('semesters', function (Blueprint $table) {
            if (!Schema::hasColumn('semesters', 'start_date')) {
                $table->date('start_date')->nullable()->after('name');
            }

            if (!Schema::hasColumn('semesters', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            if (!Schema::hasColumn('semesters', 'status')) {
                $table->string('status', 20)->default('inactive')->after('end_date');
            }
        });

        DB::table('semesters')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update([
                'status' => DB::raw("CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END"),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            if (Schema::hasColumn('semesters', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('semesters', 'end_date')) {
                $table->dropColumn('end_date');
            }

            if (Schema::hasColumn('semesters', 'start_date')) {
                $table->dropColumn('start_date');
            }
        });
    }
};
