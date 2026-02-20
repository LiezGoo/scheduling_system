<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'department_id')) {
                $table->foreignId('department_id')
                    ->nullable()
                    ->after('id')
                    ->constrained()
                    ->restrictOnDelete();
            }
        });

        DB::table('subjects')
            ->join('programs', 'subjects.program_id', '=', 'programs.id')
            ->whereNull('subjects.department_id')
            ->update(['subjects.department_id' => DB::raw('programs.department_id')]);

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_subject_code_unique');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
            $table->unique(['department_id', 'subject_code'], 'subjects_department_code_unique');
            $table->unique(['department_id', 'subject_name'], 'subjects_department_name_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique('subjects_department_code_unique');
            $table->dropUnique('subjects_department_name_unique');
            $table->unique('subject_code');
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};
