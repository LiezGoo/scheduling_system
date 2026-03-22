<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'is_nstp')) {
                $table->boolean('is_nstp')->default(false)->after('subject_type')
                    ->comment('Marks if this is an NSTP (National Service Training Program) subject');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'is_nstp')) {
                $table->dropColumn('is_nstp');
            }
        });
    }
};
