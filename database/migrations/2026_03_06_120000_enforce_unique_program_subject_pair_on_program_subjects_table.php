<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                'DELETE ps1 FROM program_subjects ps1
                 INNER JOIN program_subjects ps2
                     ON ps1.program_id = ps2.program_id
                    AND ps1.subject_id = ps2.subject_id
                    AND ps1.id > ps2.id'
            );
        } else {
            DB::statement(
                'DELETE FROM program_subjects
                 WHERE id IN (
                     SELECT id FROM (
                         SELECT id,
                                ROW_NUMBER() OVER (PARTITION BY program_id, subject_id ORDER BY id) AS duplicate_rank
                         FROM program_subjects
                     ) ranked
                     WHERE duplicate_rank > 1
                 )'
            );
        }

        Schema::table('program_subjects', function (Blueprint $table) {
            try {
                $table->dropUnique('program_subject_unique');
            } catch (Throwable $e) {
                // The legacy unique index may not exist in all environments.
            }

            $table->unique(['program_id', 'subject_id'], 'program_subject_program_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::table('program_subjects', function (Blueprint $table) {
            $table->dropUnique('program_subject_program_subject_unique');
            $table->unique(['program_id', 'subject_id', 'year_level', 'semester'], 'program_subject_unique');
        });
    }
};
