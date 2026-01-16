<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            // Add columns only if they don't already exist to keep migration idempotent after partial runs
            if (!Schema::hasColumn('departments', 'department_code')) {
                $table->string('department_code')->unique()->after('id')->nullable();
            }
            if (!Schema::hasColumn('departments', 'department_name')) {
                $table->string('department_name')->after('department_code')->nullable();
            }
        });

        // Migrate data from name to department_name and create codes
        \DB::statement('UPDATE departments SET department_name = name WHERE department_name IS NULL');
        \DB::statement('UPDATE departments SET department_code = UPPER(CONCAT(LEFT(department_name, 3), LPAD(id, 3, "0"))) WHERE department_code IS NULL');

        // Make columns non-nullable after migration
        Schema::table('departments', function (Blueprint $table) {
            // Make them non-nullable without re-adding unique (avoids duplicate index errors)
            if (Schema::hasColumn('departments', 'department_code')) {
                $table->string('department_code')->nullable(false)->change();
            }
            if (Schema::hasColumn('departments', 'department_name')) {
                $table->string('department_name')->nullable(false)->change();
            }
        });

        // Drop the old name column if it exists and is different from department_name
        if (Schema::hasColumn('departments', 'name')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'name')) {
                $table->string('name')->unique()->after('id')->nullable();
            }
            $table->dropColumn(['department_code', 'department_name']);
        });

        // Restore data if needed
        \DB::statement('UPDATE departments SET name = department_name WHERE name IS NULL');
    }
};
