<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('school_id')
                    ->nullable()
                    ->after('last_name');

                $table->index('school_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['school_id']);
                $table->dropColumn('school_id');
            });
        }
    }
};
