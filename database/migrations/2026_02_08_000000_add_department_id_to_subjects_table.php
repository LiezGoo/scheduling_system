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

        // Update subjects with department_id from programs (SQLite-compatible)
        $subjects = DB::table('subjects')
            ->whereNull('department_id')
            ->get();
        
        foreach ($subjects as $subject) {
            $program = DB::table('programs')->where('id', $subject->program_id)->first();
            if ($program && isset($program->department_id)) {
                DB::table('subjects')
                    ->where('id', $subject->id)
                    ->update(['department_id' => $program->department_id]);
            }
        }

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
