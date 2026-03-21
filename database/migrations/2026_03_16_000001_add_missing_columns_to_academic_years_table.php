<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely adds missing columns to the academic_years table.
     * This migration is idempotent — it checks before adding each column.
     */
    public function up(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            if (!Schema::hasColumn('academic_years', 'name')) {
                $table->string('name', 20)->after('id')->nullable();
            }

            if (!Schema::hasColumn('academic_years', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('end_year');
            }
        });

        // Backfill name for any existing rows that have start_year and end_year but no name
        DB::table('academic_years')
            ->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '');
            })
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('academic_years')
                    ->where('id', $row->id)
                    ->update(['name' => $row->start_year . '-' . $row->end_year]);
            });

        // Make name non-nullable now that backfill is done
        if (Schema::hasColumn('academic_years', 'name')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->string('name', 20)->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_years', function (Blueprint $table) {
            // Only drop columns if they were added by this migration
            // Checking against the original migration is not reliable on production,
            // so we leave the down() as a no-op to avoid data loss.
        });
    }
};
