<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'year_level')) {
                $table->dropColumn('year_level');
            }
            if (Schema::hasColumn('subjects', 'semester')) {
                $table->dropColumn('semester');
            }
            if (!Schema::hasColumn('subjects', 'subject_type')) {
                $table->string('subject_type', 20)->default('lecture')->after('lab_hours');
            }
            if (!Schema::hasColumn('subjects', 'description')) {
                $table->text('description')->nullable()->after('subject_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'subject_type')) {
                $table->dropColumn('subject_type');
            }
            if (Schema::hasColumn('subjects', 'description')) {
                $table->dropColumn('description');
            }
            if (!Schema::hasColumn('subjects', 'year_level')) {
                $table->unsignedTinyInteger('year_level')->default(1)->after('lab_hours');
            }
            if (!Schema::hasColumn('subjects', 'semester')) {
                $table->unsignedTinyInteger('semester')->default(1)->after('year_level');
            }
        });
    }
};
